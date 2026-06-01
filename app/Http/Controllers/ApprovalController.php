<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\ChangeRequest;
use App\Models\CrActivityLog;
use App\Models\User;
use App\Notifications\ChangeRequestApproved;
use App\Notifications\ChangeRequestAssignedToEngineer;
use App\Notifications\ChangeRequestClosed;
use App\Notifications\ChangeRequestRejected;
use App\Notifications\ChangeRequestRescheduled;
use App\Notifications\ChangeRequestWaitingApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view approval');
        // Show CRs that are currently under review or waiting approval.
        // Approved CRs are intentionally excluded from this combined list.
        $query = ChangeRequest::with(['requester', 'riskAssessment'])
            ->whereIn('status', ['under_review', 'waiting_approval', 'failed', 'rollback']);

        if (!Auth::user()->hasRole('admin')) {
            $userId = Auth::id();
            $query->where(function ($q) use ($userId) {
                $q->where('current_approver_id', $userId)
                  ->orWhere(function ($q2) use ($userId) {
                      $q2->whereIn('status', ['failed', 'rollback'])
                         ->whereJsonContains('approver_chain', $userId);
                  });
            });
        }
        if ($request->filled('status')) {
            // Tab "waiting_approval" juga tampilkan failed & rollback karena ketiganya butuh keputusan approver
            if ($request->status === 'waiting_approval') {
                $query->whereIn('status', ['waiting_approval', 'failed', 'rollback']);
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('cr_number', 'like', "%{$s}%"));
        }
        $changeRequests = $query->latest()->paginate(15)->withQueryString();
        return view('approval.index', [
            'title'          => 'Approval',
            'breadcrumbs'    => ['Dashboard' => route('dashboard'), 'Approval' => '#'],
            'changeRequests' => $changeRequests,
        ]);
    }

    public function show(ChangeRequest $cr)
    {
        $this->authorize('view approval');
        $context = in_array($cr->status, ['waiting_approval']) ? 'waiting_approval' : 'under_review';
        return redirect()->route('cr.show', $cr->id)->with('sidebar_context', $context);
    }

    public function approve(Request $request, ChangeRequest $cr)
    {
        $this->authorize('approve change_request');
        $request->validate(['note' => 'nullable|string|max:1000']);

        if (!in_array($cr->status, ['under_review', 'waiting_approval'])) {
            return redirect()->route('cr.show', $cr->id)->with('error', 'CR tidak dapat diapprove pada status ini.');
        }

        if ($cr->current_approver_id && $cr->current_approver_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $step        = (int) ($cr->current_approval_step ?: 1);
        $chain       = $cr->approver_chain ?? [];
        $totalSteps  = max(1, count($chain));
        $latestRound = Approval::where('change_request_id', $cr->id)->max('resubmit_round') ?? 1;

        $approval = Approval::where('change_request_id', $cr->id)
            ->where('approver_id', Auth::id())
            ->where('step', $step)
            ->where('resubmit_round', $latestRound)
            ->first();
        if (!$approval) {
            $approval = Approval::create([
                'change_request_id' => $cr->id,
                'approver_id'       => Auth::id(),
                'step'              => $step,
                'status'            => 'pending',
                'resubmit_round'    => $latestRound,
            ]);
        }

        $approval->update([
            'status'      => 'approved',
            'note'        => $request->note,
            'approved_at' => now(),
        ]);

        if ($step < $totalSteps) {
            $nextApproverId = $chain[$step] ?? null;
            if (!$nextApproverId) {
                // Tidak ada approver berikutnya meski step belum habis → anggap final
                $cr->update([
                    'status'                => 'approved',
                    'approver_id'           => Auth::id(),
                    'approved_at'           => now(),
                    'current_approver_id'   => null,
                    'current_approval_step' => $totalSteps,
                ]);
            } else {
                $cr->update([
                    'status'                => 'waiting_approval',
                    'approver_id'           => Auth::id(),
                    'current_approval_step' => $step + 1,
                    'current_approver_id'   => $nextApproverId,
                ]);

                Approval::where('change_request_id', $cr->id)
                    ->where('approver_id', $nextApproverId)
                    ->where('step', $step + 1)
                    ->where('resubmit_round', $latestRound)
                    ->where('status', 'pending')
                    ->update(['status' => 'submitted']);

                // Approver berikutnya mendapat notif "CR menunggu persetujuan Anda"
                $nextApprover = User::find($nextApproverId);
                if ($nextApprover) {
                    $nextApprover->notify(new ChangeRequestWaitingApproval($cr, true));
                }

                // Requester mendapat notif "CR disetujui tahap X, lanjut ke tahap X+1"
                $cr->loadMissing('requester');
                if ($cr->requester) {
                    $cr->requester->notify(new ChangeRequestApproved($cr, false, $step, $totalSteps));
                }

                CrActivityLog::create([
                    'change_request_id' => $cr->id,
                    'user_id'           => Auth::id(),
                    'type'              => 'approved_step',
                    'description'       => 'Approval tahap ' . $step . ' dari ' . $totalSteps . ' selesai.' . ($request->note ? ' Catatan: ' . $request->note : '') . ' Diteruskan ke approver berikutnya.',
                ]);
                return redirect()->route('cr.show', $cr->id)->with('success', 'Approval tahap ' . $step . ' berhasil. CR diteruskan ke approver tahap ' . ($step + 1) . '.');
            }
        } else {
            $cr->update([
                'status'              => 'approved',
                'approver_id'         => Auth::id(),
                'approved_at'         => now(),
                'current_approver_id' => null,
            ]);
        }

        // Final approval — semua tahap selesai
        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'approved',
            'description'       => 'CR disetujui (semua tahap approval selesai).' . ($request->note ? ' Catatan: ' . $request->note : ''),
        ]);

        $cr->loadMissing(['requester', 'pic']);

        // Requester: CR fully approved, menunggu jadwal
        if ($cr->requester) {
            $cr->requester->notify(new ChangeRequestApproved($cr, true, $step, $totalSteps));
        }

        // Semua approver dalam chain mendapat notif final approved
        $chain = $cr->approver_chain ?? [];
        $approverIds = collect($chain)->filter(fn($id) => $id !== Auth::id())->unique();
        foreach (User::whereIn('id', $approverIds)->get() as $approver) {
            $approver->notify(new ChangeRequestApproved($cr, true, $step, $totalSteps));
        }

        // Engineer (PIC) mendapat notif "CR menunggu jadwal Anda"
        if ($cr->pic) {
            $cr->pic->notify(new ChangeRequestAssignedToEngineer($cr));
        } else {
            $engineers = User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn($q) => $q->where('role_type', 'engineer'))
                ->get();
            foreach ($engineers as $eng) {
                $eng->notify(new ChangeRequestAssignedToEngineer($cr));
            }
        }

        return redirect()->route('cr.show', $cr->id)->with('success', 'Change Request berhasil diapprove.');
    }

    public function rescheduleDecision(Request $request, ChangeRequest $cr)
    {
        $this->authorize('approve change_request');

        if (!in_array($cr->status, ['failed', 'rollback'])) {
            return redirect()->route('cr.show', $cr->id)->with('error', 'Keputusan hanya dapat diambil pada CR berstatus Failed atau Rollback.');
        }

        // Harus approver L1 (chain[0]) atau admin
        $chain = $cr->approver_chain ?? [];
        $approverL1Id = $chain[0] ?? null;
        if ($approverL1Id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Hanya Approver Level 1 yang dapat mengambil keputusan ini.');
        }

        // Untuk failed, post-mortem wajib diisi dulu
        if ($cr->status === 'failed' && empty($cr->post_mortem_note)) {
            return redirect()->route('cr.show', $cr->id)->with('error', 'Engineer harus mengisi post-mortem terlebih dahulu sebelum keputusan dapat diambil.');
        }

        $request->validate(['action' => 'required|in:reschedule,close']);

        $prevStatus = $cr->status;

        if ($request->action === 'reschedule') {
            $cr->schedules()->update(['is_active' => false]);
            $cr->update(['status' => 'approved', 'post_mortem_note' => null]);

            CrActivityLog::create([
                'change_request_id' => $cr->id,
                'user_id'           => Auth::id(),
                'type'              => 'rescheduled',
                'description'       => 'Approver L1 mengizinkan reschedule setelah implementasi ' . $prevStatus . '. Jadwal lama dihapus, engineer dapat menetapkan jadwal baru.',
            ]);

            $cr->loadMissing(['requester', 'pic']);
            if ($cr->requester) {
                $cr->requester->notify(new ChangeRequestRescheduled($cr));
            }
            if ($cr->pic) {
                $cr->pic->notify(new ChangeRequestRescheduled($cr));
            }

            return redirect()->route('cr.show', $cr->id)->with('success', 'CR diizinkan untuk dijadwalkan ulang. Engineer dapat menetapkan jadwal baru.');
        }

        // close
        $cr->update([
            'status'        => 'closed',
            'closed_at'     => now(),
            'closed_reason' => 'failed',
        ]);

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'closed',
            'description'       => 'CR ditutup oleh Approver L1 setelah implementasi ' . $prevStatus . '. Tidak akan dilanjutkan.',
        ]);

        $cr->loadMissing('requester');
        if ($cr->requester) {
            $cr->requester->notify(new ChangeRequestClosed($cr));
        }

        return redirect()->route('cr.show', $cr->id)->with('success', 'CR ditutup.');
    }

    public function reject(Request $request, ChangeRequest $cr)
    {
        $this->authorize('reject change_request');
        $request->validate(['note' => 'required|string|max:1000']);

        if (!in_array($cr->status, ['under_review', 'waiting_approval'])) {
            return redirect()->route('cr.show', $cr->id)->with('error', 'CR tidak dapat direject pada status ini.');
        }

        if ($cr->current_approver_id && $cr->current_approver_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $step        = (int) ($cr->current_approval_step ?: 1);
        $latestRound = Approval::where('change_request_id', $cr->id)->max('resubmit_round') ?? 1;

        $approval = Approval::where('change_request_id', $cr->id)
            ->where('approver_id', Auth::id())
            ->where('step', $step)
            ->where('resubmit_round', $latestRound)
            ->first();
        if (!$approval) {
            $approval = Approval::create([
                'change_request_id' => $cr->id,
                'approver_id'       => Auth::id(),
                'step'              => $step,
                'status'            => 'pending',
                'resubmit_round'    => $latestRound,
            ]);
        }

        $approval->update([
            'status'      => 'rejected',
            'note'        => $request->note,
            'approved_at' => now(),
        ]);

        // Auto-cancel semua approval step berikutnya di round yang sama
        Approval::where('change_request_id', $cr->id)
            ->where('resubmit_round', $latestRound)
            ->where('step', '>', $step)
            ->whereIn('status', ['pending', 'submitted'])
            ->update(['status' => 'canceled']);

        $cr->update([
            'status'              => 'rejected',
            'approver_id'         => Auth::id(),
            'rejection_note'      => $request->note,
            'current_approver_id' => null,
        ]);

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'rejected',
            'description'       => 'CR ditolak. Alasan: ' . $request->note,
        ]);

        $cr->loadMissing('requester');
        if ($cr->requester) {
            $cr->requester->notify(new ChangeRequestRejected($cr, $request->note));
        }

        return redirect()->route('cr.show', $cr->id)->with('success', 'Change Request telah direject.');
    }
}
