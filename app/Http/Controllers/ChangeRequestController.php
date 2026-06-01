<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\CrActivityLog;
use App\Models\CrOption;
use App\Models\User;
use App\Models\Approval;
use App\Models\ApprovalRule;
use App\Models\Role;
use App\Notifications\ChangeRequestClosed;
use App\Notifications\ChangeRequestInProgress;
use App\Notifications\ChangeRequestNeedsApproval;
use App\Notifications\ChangeRequestNeedReview;
use App\Notifications\ChangeRequestSubmitted;
use App\Support\ApprovalChain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view change_request');
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if (!$authUser) {
            abort(401);
        }

        $user = $authUser;
        $query = ChangeRequest::with(['requester', 'approver', 'riskAssessment']);
        $this->applyVisibilityScope($query, $user);

        if ($request->filled('status')) {
            $raw = $request->status;
            if (str_starts_with($raw, 'closed:')) {
                // Format "closed:reason" — filter status=closed + closed_reason
                $reason = substr($raw, 7);
                $query->where('status', 'closed')->where('closed_reason', $reason);
            } else {
                $statuses = is_array($raw) ? $raw : explode(',', $raw);
                count($statuses) === 1
                    ? $query->where('status', $statuses[0])
                    : $query->whereIn('status', $statuses);
            }
        }
        if ($request->filled('risk_level')) $query->where('risk_level', $request->risk_level);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('title','like',"%{$s}%")->orWhere('cr_number','like',"%{$s}%")->orWhere('affected_service','like',"%{$s}%"));
        }
        $changeRequests = $query->latest()->paginate(15)->withQueryString();
        return view('cr.index', [
            'title'          => 'Change Request',
            'breadcrumbs'    => ['Dashboard' => route('dashboard'), 'Change Request' => '#'],
            'changeRequests' => $changeRequests,
        ]);
    }

    public function create()
    {
        $this->authorize('create change_request');
        return view('cr.create', [
            'title'       => 'Buat CR Baru',
            'breadcrumbs' => ['Dashboard'=>route('dashboard'),'Change Request'=>route('cr.index'),'Buat Baru'=>'#'],
            'engineers'   => $this->eligiblePicUsers(),
            'cr'          => null,
            'crOptions'   => $this->crOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create change_request');
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'required|string',
            'reason'           => 'required|string',
            'affected_service' => 'required|string|max:255',
            'change_type'      => 'required|in:' . implode(',', CrOption::valuesForType('change_type')),
            'category'         => 'required|in:' . implode(',', CrOption::valuesForType('category')),
            'priority'         => 'required|in:' . implode(',', CrOption::valuesForType('priority')),
            'rollback_plan'    => 'required|string',
            'impact'           => 'nullable|string',
            'pic_id'           => 'required|exists:users,id',
        ]);
        $cr = ChangeRequest::create([
            ...$validated,
            'cr_number'    => ChangeRequest::generateCrNumber(),
            'risk_level'   => 'medium',
            'status'       => 'draft',
            'requester_id' => Auth::id(),
        ]);
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments/'.$cr->id,'public');
                $cr->attachments()->create([
                    'filename'      => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                    'uploaded_by'   => Auth::id(),
                ]);
            }
        }
        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'created',
            'description'       => 'CR dibuat sebagai draft.',
        ]);

        return redirect()->route('cr.show',$cr->id)->with('success','Change Request '.$cr->cr_number.' berhasil dibuat.');
    }

    public function show(ChangeRequest $cr)
    {
        $this->authorize('view change_request');
        $cr->load(['requester','approver','pic','riskAssessment','approvals.approver','schedule.pic','implementationLogs.implementer','attachments','activityLogs.user']);

        if (in_array($cr->status, ['submitted', 'need_review', 'under_review', 'waiting_approval'])) {
            $this->healApprovalChain($cr);
        }

        // Auto-mark semua notifikasi terkait CR ini sebagai dibaca
        Auth::user()->unreadNotifications()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.cr_id')) = ?", [$cr->id])
            ->get()
            ->markAsRead();

        return view('cr.show', [
            'title'       => $cr->cr_number,
            'breadcrumbs' => ['Dashboard'=>route('dashboard'),'Change Request'=>route('cr.index'),$cr->cr_number=>'#'],
            'cr'          => $cr,
        ]);
    }

    public function edit(ChangeRequest $cr)
    {
        $this->authorize('edit change_request');
        if (!in_array($cr->status,['draft','rejected'])) {
            return redirect()->route('cr.show',$cr->id)->with('error','CR tidak dapat diedit pada status '.$cr->status.'.');
        }
        return view('cr.edit', [
            'title'       => 'Edit CR',
            'breadcrumbs' => ['Dashboard'=>route('dashboard'),'Change Request'=>route('cr.index'),$cr->cr_number=>route('cr.show',$cr->id),'Edit'=>'#'],
            'cr'          => $cr,
            'engineers'   => $this->eligiblePicUsers(),
            'crOptions'   => $this->crOptions(),
        ]);
    }

    public function update(Request $request, ChangeRequest $cr)
    {
        $this->authorize('edit change_request');
        if (!in_array($cr->status,['draft','rejected'])) {
            return redirect()->route('cr.show',$cr->id)->with('error','CR tidak dapat diedit.');
        }
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'required|string',
            'reason'           => 'required|string',
            'affected_service' => 'required|string|max:255',
            'change_type'      => 'required|in:' . implode(',', CrOption::valuesForType('change_type')),
            'category'         => 'required|in:' . implode(',', CrOption::valuesForType('category')),
            'priority'         => 'required|in:' . implode(',', CrOption::valuesForType('priority')),
            'rollback_plan'    => 'required|string',
            'impact'           => 'nullable|string',
            'pic_id'           => 'required|exists:users,id',
            'delete_attachments' => 'nullable|array',
            'delete_attachments.*' => 'integer|exists:cr_attachments,id',
        ]);
        $fieldLabels = [
            'title'            => 'Judul',
            'description'      => 'Deskripsi',
            'reason'           => 'Alasan',
            'affected_service' => 'Layanan Terdampak',
            'change_type'      => 'Tipe Change',
            'category'         => 'Kategori',
            'priority'         => 'Prioritas',
            'rollback_plan'    => 'Rollback Plan',
            'impact'           => 'Potensi Dampak',
            'pic_id'           => 'PIC Implementasi',
        ];
        $changed = [];
        $truncate = fn($v) => mb_strlen((string)$v) > 100 ? mb_substr((string)$v, 0, 100).'...' : (string)$v;
        foreach ($validated as $field => $newVal) {
            if (in_array($field, ['delete_attachments'])) continue;
            $oldVal = $cr->getAttribute($field);
            if ((string)$oldVal !== (string)($newVal ?? '')) {
                $label = $fieldLabels[$field] ?? $field;
                if ($field === 'pic_id') {
                    $changed[] = [
                        'field'  => $label,
                        'before' => $oldVal ? (\App\Models\User::find($oldVal)?->name ?? $oldVal) : '-',
                        'after'  => $newVal ? (\App\Models\User::find($newVal)?->name ?? $newVal) : '-',
                    ];
                } else {
                    $changed[] = [
                        'field'  => $label,
                        'before' => $oldVal !== null && $oldVal !== '' ? $truncate($oldVal) : '-',
                        'after'  => $newVal !== null && $newVal !== '' ? $truncate($newVal) : '-',
                    ];
                }
            }
        }

        $cr->update(\Illuminate\Support\Arr::except($validated, ['delete_attachments']));

        if (!empty($validated['delete_attachments'])) {
            $toDelete = $cr->attachments()->whereIn('id', $validated['delete_attachments'])->get();
            foreach ($toDelete as $att) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($att->filename);
                $changed[] = ['field' => 'Lampiran', 'before' => $att->original_name, 'after' => '[Dihapus]'];
                $att->delete();
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments/'.$cr->id,'public');
                $cr->attachments()->create([
                    'filename'      => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                    'uploaded_by'   => Auth::id(),
                ]);
                $changed[] = ['field' => 'Lampiran', 'before' => '-', 'after' => $file->getClientOriginalName()];
            }
        }

        $desc = empty($changed)
            ? 'Draft CR diperbarui (tidak ada perubahan).'
            : json_encode(['_type' => 'diff', 'changes' => $changed], JSON_UNESCAPED_UNICODE);

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'updated',
            'description'       => $desc,
        ]);

        return redirect()->route('cr.show',$cr->id)->with('success','Change Request berhasil diperbarui.');
    }

    public function submit(ChangeRequest $cr)
    {
        $this->authorize('submit change_request');

        /** @var User|null $authUser */
        $authUser = Auth::user();
        if (!$authUser) {
            abort(401);
        }

        $requester = $authUser;

        if (!$requester->can('create change_request')) {
            abort(403, 'Role ini tidak memiliki akses membuat CR.');
        }

        if ($cr->requester_id !== Auth::id() && !$requester->hasRole('admin')) abort(403);
        if (!in_array($cr->status,['draft','rejected'])) {
            return redirect()->route('cr.show',$cr->id)->with('error','CR tidak dapat disubmit.');
        }

        $requiredLevels = ApprovalRule::resolveMaxLevelsForChangeRequest($cr);
        $chain = ApprovalChain::buildForChangeRequest($cr, $requester);
        if (count($chain) === 0) {
            return redirect()->route('cr.show', $cr->id)->with('error', 'Tidak ada approver yang tersedia. Hubungi admin.');
        }
        if (count($chain) < $requiredLevels) {
            return redirect()->route('cr.show', $cr->id)->with('error', 'Jenjang atasan belum lengkap untuk priority ini (butuh '.$requiredLevels.' level). Hubungi admin untuk set atasan user.');
        }

        $isResubmit = $cr->status === 'rejected';

        $cr->update([
            'status'                => 'need_review',
            'submitted_at'          => now(),
            'approver_chain'        => $chain,
            'current_approval_step' => 1,
            'current_approver_id'   => $chain[0],
            'approver_id'           => $chain[0],
            'rejection_note'        => null,
        ]);

        if ($isResubmit) {
            // Simpan history lama, buat set approval baru dengan round berikutnya
            $nextRound = (Approval::where('change_request_id', $cr->id)->max('resubmit_round') ?? 1) + 1;
            foreach ($chain as $i => $approverId) {
                Approval::create([
                    'change_request_id' => $cr->id,
                    'approver_id'       => $approverId,
                    'step'              => $i + 1,
                    'status'            => $i === 0 ? 'submitted' : 'pending',
                    'resubmit_round'    => $nextRound,
                ]);
            }
        } else {
            Approval::where('change_request_id', $cr->id)->delete();
            foreach ($chain as $i => $approverId) {
                Approval::create([
                    'change_request_id' => $cr->id,
                    'approver_id'       => $approverId,
                    'step'              => $i + 1,
                    'status'            => $i === 0 ? 'submitted' : 'pending',
                    'resubmit_round'    => 1,
                ]);
            }
        }

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'submitted',
            'description'       => $isResubmit
                ? 'CR disubmit ulang setelah ditolak dan menunggu review dari approver.'
                : 'CR disubmit dan menunggu review dari approver.',
        ]);

        $firstApprover = User::find($chain[0]);
        if ($firstApprover) {
            $firstApprover->notify(new ChangeRequestNeedReview($cr, $firstApprover, 1, count($chain)));
        }
        $requester->notify(new ChangeRequestSubmitted($cr, $firstApprover, 1, count($chain)));

        return redirect()->route('cr.show',$cr->id)->with('success','Change Request berhasil disubmit. Menunggu review dari approver.');
    }

    public function cancel(Request $request, ChangeRequest $cr)
    {
        $this->authorize('cancel change_request');

        if ($cr->requester_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        if ($cr->status !== 'draft') {
            return redirect()->route('cr.show', $cr->id)->with('error', 'CR hanya dapat dibatalkan saat masih berstatus Draft.');
        }

        $validated = $request->validate([
            'cancellation_note' => 'required|string|min:2',
        ], [
            'cancellation_note.required' => 'Alasan pembatalan wajib diisi.',
            'cancellation_note.min'      => 'Alasan pembatalan minimal 2 karakter.',
        ]);

        $cr->update([
            'status'             => 'canceled',
            'cancellation_note'  => $validated['cancellation_note'],
        ]);

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'canceled',
            'description'       => 'CR dibatalkan. Alasan: ' . $validated['cancellation_note'],
        ]);

        return redirect()->route('cr.show', $cr->id)->with('success', 'Change Request berhasil dibatalkan.');
    }

    public function closeRejected(ChangeRequest $cr)
    {
        $this->authorize('cancel change_request');

        if ($cr->requester_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        if ($cr->status !== 'rejected') {
            return redirect()->route('cr.show', $cr->id)->with('error', 'Hanya CR yang berstatus Rejected yang dapat ditutup melalui aksi ini.');
        }

        $cr->update([
            'status'        => 'closed',
            'closed_at'     => now(),
            'closed_reason' => 'rejected',
        ]);

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'closed',
            'description'       => 'CR ditutup oleh requester setelah ditolak. Tidak akan dilanjutkan.',
        ]);

        // Notif canceled ke semua pihak terkait (Approvers)
        $cr->loadMissing('requester');
        $recipients = collect([$cr->requester])->filter();
        $chainIds   = collect($cr->approver_chain ?? [])->filter()->unique();
        if ($chainIds->isNotEmpty()) {
            $recipients = $recipients->merge(\App\Models\User::whereIn('id', $chainIds)->get());
        }
        foreach ($recipients->unique('id') as $recipient) {
            $recipient->notify(new \App\Notifications\ChangeRequestCanceled($cr, 'Ditutup oleh requester setelah ditolak.'));
        }

        return redirect()->route('cr.show', $cr->id)->with('success', 'Change Request ditutup secara permanen.');
    }

    public function startImplementation(ChangeRequest $cr)
    {
        $this->authorize('create implementation');

        /** @var User $authUser */
        $authUser = Auth::user();
        if ($cr->pic_id !== Auth::id() && !$authUser->hasRole('admin')) {
            abort(403, 'Hanya PIC yang ditugaskan yang dapat memulai implementasi.');
        }

        if ($cr->status !== 'scheduled') {
            return redirect()->route('cr.show', $cr->id)->with('error', 'Implementasi hanya dapat dimulai saat CR berstatus Scheduled.');
        }

        $cr->update(['status' => 'in_progress']);

        if ($cr->schedule) {
            $cr->schedule->update(['actual_start' => now()]);
        }

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'in_progress',
            'description'       => 'Implementasi dimulai oleh ' . $authUser->name . ' pada ' . now()->format('d M Y H:i') . '.',
        ]);

        // Notif ke requester + PIC/engineer + semua approver chain
        $cr->loadMissing(['requester', 'pic']);
        $recipients = collect([$cr->requester, $cr->pic])->filter();
        $chainIds   = collect($cr->approver_chain ?? [])->filter()->unique();
        if ($chainIds->isNotEmpty()) {
            $recipients = $recipients->merge(User::whereIn('id', $chainIds)->get());
        }
        foreach ($recipients->unique('id') as $recipient) {
            $recipient->notify(new ChangeRequestInProgress($cr));
        }

        return redirect()->route('cr.show', $cr->id)->with('success', 'Implementasi dimulai. Waktu mulai dicatat otomatis: ' . now()->format('d M Y H:i') . '.');
    }

    public function destroy(ChangeRequest $cr)
    {
        $this->authorize('delete change_request');
        if ($cr->status !== 'draft') {
            return redirect()->route('cr.index')->with('error','Hanya CR draft yang dapat dihapus.');
        }
        $cr->delete();
        return redirect()->route('cr.index')->with('success','Change Request berhasil dihapus.');
    }

    private function healApprovalChain(ChangeRequest $cr): void
    {
        $latestRound = $cr->approvals->max('resubmit_round') ?? 1;
        $approvals = $cr->approvals->where('resubmit_round', $latestRound)->sortBy('step');
        if ($approvals->isEmpty()) return;

        $chain = $approvals->pluck('approver_id')->values()->toArray();
        $updates = [];

        // Selalu sync approver_chain dari approvals round terbaru
        if ($cr->approver_chain !== $chain) {
            $updates['approver_chain'] = $chain;
        }

        $activeApproval = $approvals->first(fn($a) => in_array($a->status, ['submitted', 'pending']));
        if (!$activeApproval) return;

        $correctStep = $activeApproval->step;
        $correctApproverId = $activeApproval->approver_id;

        if ((int)$cr->current_approval_step !== $correctStep) {
            $updates['current_approval_step'] = $correctStep;
        }
        if ((string)$cr->current_approver_id !== (string)$correctApproverId) {
            $updates['current_approver_id'] = $correctApproverId;
            $updates['approver_id'] = $correctApproverId;
        }

        foreach ($approvals as $approval) {
            if ($approval->step < $correctStep && $approval->status === 'pending') {
                $approval->update(['status' => 'approved']);
            }
            if ($approval->step === $correctStep && $approval->status === 'pending') {
                $approval->update(['status' => 'submitted']);
            }
        }

        if (!empty($updates)) {
            ChangeRequest::where('id', $cr->id)->update($updates);
            $cr->refresh();
        }
    }

    private function crOptions(): array
    {
        return [
            'change_types' => CrOption::ofType('change_type'),
            'categories'   => CrOption::ofType('category'),
            'priorities'   => CrOption::ofType('priority'),
        ];
    }

    private function eligiblePicUsers()
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        // Admin: semua user bertipe engineer
        if ($authUser->hasRole('admin')) {
            return User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn($q) => $q->where('role_type', 'engineer'))
                ->orderBy('name')
                ->get();
        }

        $userRole = $authUser->roles()->orderBy('level')->first();
        if ($userRole) {
            // Jika user punya atasan (parent), ambil semua turunan dari atasan tersebut (termasuk cabang lain)
            // Jika tidak (misal top level), ambil turunan dari user itu sendiri.
            $baseRole = $userRole->parent ?? $userRole;
            $allowedRoleIds = $baseRole->descendants()
                ->where('role_type', 'engineer')
                ->pluck('id')
                ->all();

            if (!empty($allowedRoleIds)) {
                return User::query()
                    ->where('is_active', true)
                    ->whereHas('roles', fn($q) => $q->whereIn('roles.id', $allowedRoleIds))
                    ->orderBy('name')
                    ->get();
            }
        }

        return collect();
    }
}
