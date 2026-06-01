<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\ChangeRequest;
use App\Models\CrActivityLog;
use App\Notifications\ChangeRequestUnderReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NeedReviewController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view need_review');
        $query = ChangeRequest::with(['requester', 'riskAssessment'])
            ->where('status', 'need_review');

        if (!Auth::user()->hasRole('admin')) {
            $query->where('current_approver_id', Auth::id());
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('cr_number', 'like', "%{$s}%"));
        }

        $changeRequests = $query->latest()->paginate(15)->withQueryString();
        return view('need-review.index', [
            'title'          => 'Need Review',
            'breadcrumbs'    => ['Dashboard' => route('dashboard'), 'Need Review' => '#'],
            'changeRequests' => $changeRequests,
        ]);
    }

    public function show(ChangeRequest $cr)
    {
        $this->authorize('view need_review');
        return redirect()->route('cr.show', $cr->id)->with('sidebar_context', 'need_review');
    }

    public function startReview(ChangeRequest $cr)
    {
        $this->authorize('view need_review');

        if ($cr->status !== 'need_review') {
            return redirect()->route('cr.show', $cr->id)->with('error', 'CR tidak dalam status need review.');
        }

        if ($cr->current_approver_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $cr->update(['status' => 'under_review']);

        // Detailed activity log
        $approvalStep = $cr->current_approval_step ?? 1;
        $totalSteps = count($cr->approver_chain ?? []);
        $description = 'Approver memulai review CR. Status: Tahap '.$approvalStep.' dari '.$totalSteps.'. CR siap untuk diisi Risk Assessment.';

        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'review_started',
            'description'       => $description,
        ]);

        $cr->loadMissing('requester');
        if ($cr->requester) {
            $cr->requester->notify(new ChangeRequestUnderReview($cr));
        }

        return redirect()->route('cr.show', $cr->id)->with('success', 'CR sekarang dalam proses Under Review. Silakan isi Risk Assessment.');
    }
}
