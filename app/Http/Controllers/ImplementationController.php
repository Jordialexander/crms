<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\CrActivityLog;
use App\Models\ImplementationLog;
use App\Models\User;
use App\Notifications\ChangeRequestClosed;
use App\Notifications\ChangeRequestImplementationCompleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImplementationController extends Controller
{
    public function store(Request $request, ChangeRequest $cr)
    {
        $this->authorize('create implementation');

        /** @var User $user */
        $user = Auth::user();
        $isPic = $cr->pic_id === Auth::id();
        $hasWideAccess = $user->can('view all change_request') || $user->can('view team change_request');
        if (!$isPic && !$hasWideAccess) {
            abort(403, 'Hanya PIC yang ditugaskan yang dapat mengisi log implementasi.');
        }

        if ($cr->status !== 'in_progress') {
            return redirect()->back()->with('error', 'Log implementasi hanya dapat diisi saat CR berstatus In Progress.');
        }

        $validated = $request->validate([
            'result_status'    => 'required|in:success,failed,rollback',
            'result_note'      => 'required|string',
            'issues'           => 'nullable|string',
            'post_review_note' => 'nullable|string',
            'evidence_file'    => 'nullable|file|max:10240',
        ]);

        $actualEnd = now();

        $evidencePath = null;
        if ($request->hasFile('evidence_file')) {
            $evidencePath = $request->file('evidence_file')->store('evidence/'.$cr->id,'public');
        }
        ImplementationLog::create([
            ...$validated,
            'change_request_id' => $cr->id,
            'implementer_id'    => Auth::id(),
            'evidence_file'     => $evidencePath,
            'actual_start'      => $cr->schedule?->actual_start,
            'actual_end'        => $actualEnd,
        ]);

        $newStatus = match($validated['result_status']) {
            'success'  => 'completed',
            'failed'   => 'failed',
            'rollback' => 'rollback',
        };
        $cr->update(['status' => $newStatus]);

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'implementation_done',
            'description'       => 'Implementasi selesai dengan status: '.strtoupper($validated['result_status']).'. '.$validated['result_note'],
        ]);

        // Catat actual_end otomatis ke schedule
        if ($cr->schedule) {
            $cr->schedule->update(['actual_end' => $actualEnd]);
        }

        $cr->loadMissing(['requester', 'approver']);
        $recipients = collect([$cr->requester])->filter();
        $chain = $cr->approver_chain ?? [];
        if (count($chain) > 0) {
            $recipients = $recipients->merge(User::whereIn('id', $chain)->get());
        }
        $recipients = $recipients->unique('id');
        foreach ($recipients as $recipient) {
            $recipient->notify(new ChangeRequestImplementationCompleted($cr));
        }

        return redirect()->back()->with('success','Log implementasi disimpan. Status: '.strtoupper($newStatus));
    }

    public function postMortem(Request $request, ChangeRequest $cr)
    {
        $this->authorize('create implementation');

        /** @var User $user */
        $user = Auth::user();
        if ($cr->pic_id !== Auth::id() && !$user->can('view all change_request') && !$user->can('view team change_request')) {
            abort(403, 'Hanya PIC yang dapat mengisi post-mortem.');
        }

        if (!in_array($cr->status, ['failed', 'rollback'])) {
            return redirect()->back()->with('error', 'Post-mortem hanya dapat diisi saat CR berstatus Failed atau Rollback.');
        }

        $validated = $request->validate([
            'post_mortem_note' => 'required|string|min:10',
        ], [
            'post_mortem_note.required' => 'Analisis kegagalan wajib diisi.',
            'post_mortem_note.min'      => 'Analisis kegagalan minimal 10 karakter.',
        ]);

        $cr->update(['post_mortem_note' => $validated['post_mortem_note']]);

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'post_mortem',
            'description'       => 'Post-mortem diisi oleh engineer: ' . $validated['post_mortem_note'],
        ]);

        // Notifikasi ke approver L1 bahwa post-mortem sudah diisi
        $chain = $cr->approver_chain ?? [];
        if (!empty($chain)) {
            $approverL1 = User::find($chain[0]);
            if ($approverL1) {
                $approverL1->notify(new \App\Notifications\ChangeRequestPostMortemFilled($cr));
            }
        }

        return redirect()->back()->with('success', 'Post-mortem berhasil disimpan. Menunggu keputusan approver.');
    }

    public function close(Request $request, ChangeRequest $cr)
    {
        $this->authorize('edit implementation');
        if ($cr->status !== 'completed') {
            return redirect()->back()->with('error', 'CR hanya dapat ditutup oleh engineer saat berstatus Completed.');
        }

        $request->validate([
            'closing_note' => 'required|string|min:5',
        ], [
            'closing_note.required' => 'Catatan penutupan wajib diisi.',
            'closing_note.min'      => 'Catatan minimal 5 karakter.',
        ]);

        $cr->update([
            'status'       => 'closed',
            'closed_at'    => now(),
            'closed_reason'=> 'completed',
            'closing_note' => $request->closing_note,
        ]);

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'closed',
            'description'       => 'CR ditutup setelah implementasi berhasil.',
        ]);

        // Notif closed ke requester + PIC + semua approver chain
        $cr->loadMissing(['requester', 'pic']);
        $recipients = collect([$cr->requester, $cr->pic])->filter();
        $chainIds   = collect($cr->approver_chain ?? [])->filter()->unique();
        if ($chainIds->isNotEmpty()) {
            $recipients = $recipients->merge(User::whereIn('id', $chainIds)->get());
        }
        foreach ($recipients->unique('id') as $recipient) {
            $recipient->notify(new ChangeRequestClosed($cr));
        }

        return redirect()->back()->with('success', 'Change Request berhasil ditutup.');
    }
}
