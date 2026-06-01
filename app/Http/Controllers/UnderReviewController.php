<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\ChangeRequest;
use App\Models\CrActivityLog;
use App\Notifications\ChangeRequestWaitingApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnderReviewController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view under_review');
        $query = ChangeRequest::with(['requester', 'riskAssessment'])
            ->where('status', 'under_review');

        if (!Auth::user()->hasRole('admin')) {
            $query->where('current_approver_id', Auth::id());
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('cr_number', 'like', "%{$s}%"));
        }

        $changeRequests = $query->latest()->paginate(15)->withQueryString();
        return view('under-review.index', [
            'title'          => 'Under Review',
            'breadcrumbs'    => ['Dashboard' => route('dashboard'), 'Under Review' => '#'],
            'changeRequests' => $changeRequests,
        ]);
    }

    public function show(ChangeRequest $cr)
    {
        $this->authorize('view under_review');
        return redirect()->route('cr.show', $cr->id);
    }

    public function submitForApproval(Request $request, ChangeRequest $cr)
    {
        $this->authorize('view under_review');

        if ($cr->status !== 'under_review') {
            return redirect()->route('cr.show', $cr->id)->with('error', 'CR tidak dalam status under review.');
        }

        if ($cr->current_approver_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        if (!$cr->riskAssessment) {
            return redirect()->route('cr.show', $cr->id)
                ->with('error', 'Isi Risk Assessment terlebih dahulu sebelum melanjutkan ke approval.');
        }

        $cr->update(['status' => 'waiting_approval']);

        // Detailed activity log with risk assessment summary
        $ra = $cr->riskAssessment;
        $raDetails = 'Dampak='.$ra->impact_score.', Kompleksitas='.$ra->complexity_score.
                     ', UserImpact='.$ra->user_impact_score.', FailureProb='.$ra->failure_probability_score.
                     ', Total='.$ra->total_score.', Level='.$ra->risk_level;
        $description = 'Risk Assessment selesai ('.$raDetails.'). CR diteruskan ke tahap approval.';

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'review_completed',
            'description'       => $description,
        ]);

        $cr->loadMissing(['requester', 'currentApprover']);

        // Approver yang perlu action
        if ($cr->currentApprover) {
            $cr->currentApprover->notify(new ChangeRequestWaitingApproval($cr, true));
        }

        // Requester dapat update status
        if ($cr->requester) {
            $cr->requester->notify(new ChangeRequestWaitingApproval($cr, false));
        }

        return redirect()->route('cr.show', $cr->id)->with('success', 'CR siap untuk di-approve.');
    }
}
