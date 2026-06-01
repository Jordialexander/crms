<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\ChangeSchedule;
use App\Models\CrActivityLog;
use App\Notifications\ChangeRequestScheduled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view schedule');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tab  = $request->get('tab', 'unscheduled');

        // Tentukan status yang termasuk di tiap tab
        $tabStatuses = [
            'unscheduled' => ['approved'],
            'scheduled'   => ['scheduled'],
            'in_progress' => ['in_progress'],
            'completed'   => ['completed'],
        ];

        $statuses = $tabStatuses[$tab] ?? $tabStatuses['unscheduled'];

        $query = \App\Models\ChangeRequest::with(['requester', 'pic', 'schedule'])
            ->whereIn('status', $statuses);

        // Scope ke CR yang user terlibat: sebagai PIC, requester, atau ada di approver_chain
        if (!$user->can('view all change_request') && !$user->can('view team change_request')) {
            $uid = $user->id;
            $query->where(fn($q) => $q
                ->where('pic_id', $uid)
                ->orWhere('requester_id', $uid)
                ->orWhereJsonContains('approver_chain', $uid)
            );
        }

        // Untuk tab yang punya jadwal, urutkan berdasarkan planned_start
        if (in_array($tab, ['scheduled', 'in_progress'])) {
            $query->whereHas('schedule')->orderByDesc('updated_at');
        } else {
            $query->orderByDesc('updated_at');
        }

        $changeRequests = $query->paginate(15)->withQueryString();

        // Hitung badge count per tab (scoped ke hierarki CR jika bukan admin/team)
        $isLimited = !$user->can('view all change_request') && !$user->can('view team change_request');
        $uid = $user->id;
        $countQuery = fn($s) => \App\Models\ChangeRequest::whereIn('status', $s)
            ->when($isLimited, fn($q) => $q->where(fn($q2) => $q2
                ->where('pic_id', $uid)
                ->orWhere('requester_id', $uid)
                ->orWhereJsonContains('approver_chain', $uid)
            ))
            ->count();

        $counts = [
            'unscheduled' => $countQuery(['approved']),
            'scheduled'   => $countQuery(['scheduled']),
            'in_progress' => $countQuery(['in_progress']),
            'completed'   => $countQuery(['completed']),
        ];

        return view('schedule.index', [
            'title'          => 'Jadwal Implementasi',
            'breadcrumbs'    => ['Dashboard' => route('dashboard'), 'Jadwal' => '#'],
            'changeRequests' => $changeRequests,
            'tab'            => $tab,
            'counts'         => $counts,
        ]);
    }

    public function store(Request $request, ChangeRequest $cr)
    {
        $this->authorize('create schedule');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isPic = $cr->pic_id === $user->id;
        $isAdmin = $user->hasRole('admin');
        if (!$isPic && !$isAdmin) {
            abort(403, 'Hanya engineer yang ditugaskan sebagai PIC yang dapat menetapkan jadwal implementasi.');
        }

        $isUpdate = $cr->status === 'scheduled' && $cr->schedule;

        if (!in_array($cr->status, ['approved', 'scheduled'])) {
            return redirect()->back()->with('error', 'Jadwal hanya dapat dibuat atau diubah sebelum implementasi dimulai.');
        }

        $validated = $request->validate([
            'planned_start'              => 'required|date',
            'planned_end'                => 'required|date|after:planned_start',
            'estimated_downtime_minutes' => 'required|integer|min:0',
            'pic_id'                     => 'nullable|exists:users,id',
            'notes'                      => 'nullable|string',
        ]);

        if ($isUpdate) {
            // Edit jadwal aktif di round yang sama — update langsung
            $cr->schedule->update($validated);
        } else {
            // Jadwal baru (approved → scheduled, atau setelah reschedule)
            $nextRound = ($cr->schedules()->max('round') ?? 0) + 1;
            ChangeSchedule::create(array_merge($validated, [
                'change_request_id' => $cr->id,
                'round'             => $nextRound,
                'is_active'         => true,
            ]));
        }
        $cr->update(['status' => 'scheduled']);

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'scheduled',
            'description'       => ($isUpdate ? 'Jadwal implementasi diperbarui: ' : 'Jadwal implementasi ditetapkan: ')
                .date('d M Y H:i', strtotime($validated['planned_start']))
                .' s/d '.date('d M Y H:i', strtotime($validated['planned_end'])).'.'.
                ($validated['notes'] ? ' Catatan: '.$validated['notes'] : ''),
        ]);

        $cr->loadMissing(['requester', 'pic']);
        $recipients = collect([$cr->requester, $cr->pic])->filter();
        $chainIds   = collect($cr->approver_chain ?? [])->filter()->unique();
        if ($chainIds->isNotEmpty()) {
            $recipients = $recipients->merge(\App\Models\User::whereIn('id', $chainIds)->get());
        }
        foreach ($recipients->unique('id') as $u) {
            $u->notify(new ChangeRequestScheduled($cr, $isUpdate));
        }

        return redirect()->back()->with('success', $isUpdate ? 'Jadwal implementasi berhasil diperbarui.' : 'Jadwal implementasi berhasil disimpan.');
    }

    public function reschedule(ChangeRequest $cr)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan reschedule melalui aksi ini.');
        }

        if (!in_array($cr->status, ['rollback', 'failed'])) {
            return redirect()->back()->with('error', 'Reschedule hanya dapat dilakukan pada CR berstatus Rollback atau Failed.');
        }

        // Nonaktifkan jadwal lama (simpan sebagai history, tidak dihapus)
        $cr->schedules()->update(['is_active' => false]);
        $cr->update(['status' => 'approved', 'post_mortem_note' => null]);

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'rescheduled',
            'description'       => 'CR di-reschedule oleh admin. Jadwal lama diarsipkan.',
        ]);

        return redirect()->back()->with('success', 'CR berhasil di-reschedule. Engineer dapat menetapkan jadwal baru.');
    }
}
