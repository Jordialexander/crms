<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\CrActivityLog;
use App\Models\RiskAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiskAssessmentController extends Controller
{
    public function store(Request $request, ChangeRequest $cr)
    {
        $this->authorize('approve change_request');

        // Hanya approver aktif (current_approver) yang boleh mengisi risk assessment
        if ($cr->current_approver_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            $msg = 'Hanya approver yang sedang bertugas yang dapat mengisi risk assessment.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            abort(403, $msg);
        }

        $validated = $request->validate([
            'impact_score'              => 'required|integer|min:1|max:5',
            'complexity_score'          => 'required|integer|min:1|max:5',
            'user_impact_score'         => 'required|integer|min:1|max:5',
            'failure_probability_score' => 'required|integer|min:1|max:5',
            'notes'                     => 'nullable|string',
        ]);
        
        $total     = array_sum(array_filter($validated, fn($k) => str_ends_with($k,'_score'), ARRAY_FILTER_USE_KEY));
        $riskLevel = RiskAssessment::calculateRiskLevel($total);
        
        $isUpdate = $cr->riskAssessment ? true : false;
        
        $cr->riskAssessment()->updateOrCreate(
            ['change_request_id'=>$cr->id],
            [...$validated,'total_score'=>$total,'risk_level'=>$riskLevel,'assessed_by'=>Auth::id()]
        );
        $cr->update(['risk_level'=>$riskLevel]);

        // Setelah risk assessment disimpan, tandai CR sebagai menunggu approval
        // agar approver dapat langsung mengambil tindakan tanpa perlu refresh.
        if (!in_array($cr->status, ['waiting_approval','approved','rejected'])) {
            $cr->update(['status' => 'waiting_approval']);
        }

        // Notify requester that CR is waiting approval
        $cr->loadMissing('requester');
        if ($cr->requester) {
            try {
                if ($isUpdate) {
                    $cr->requester->notify(new \App\Notifications\ChangeRequestRiskAssessmentEdited($cr));
                } else {
                    $cr->requester->notify(new \App\Notifications\ChangeRequestRiskAssessmentFilled($cr));
                }
            } catch (\Throwable $e) {
                // swallow notification errors to not break AJAX flow
            }
        }
        
        // Log activity dengan detail
        $details = [
            'impact_score' => $validated['impact_score'],
            'complexity_score' => $validated['complexity_score'],
            'user_impact_score' => $validated['user_impact_score'],
            'failure_probability_score' => $validated['failure_probability_score'],
            'total_score' => $total,
            'risk_level' => $riskLevel,
            'notes' => $validated['notes'] ?? '-',
        ];
        
        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'risk_assessment',
            'description'       => ($isUpdate ? 'Risk Assessment diupdate. ' : 'Risk Assessment diisi. ') . 
                                   'Detail: Dampak='.$validated['impact_score'].', Kompleksitas='.$validated['complexity_score'].
                                   ', UserImpact='.$validated['user_impact_score'].', FailureProb='.$validated['failure_probability_score'].
                                   ', Total='.$total.', Level='.strtoupper($riskLevel),
        ]);
        
        CrActivityLog::create([
            'change_request_id' => $cr->id,
            'user_id'           => Auth::id(),
            'type'              => 'review_completed',
            'description'       => 'Risk Assessment disimpan dan CR diteruskan ke tahap approval. Level='.strtoupper($riskLevel).'.',
        ]);
        
        $successMsg = 'Risk Assessment disimpan. Level: '.strtoupper($riskLevel);
        
        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'risk_assessment' => [
                    'impact_score' => $validated['impact_score'],
                    'complexity_score' => $validated['complexity_score'],
                    'user_impact_score' => $validated['user_impact_score'],
                    'failure_probability_score' => $validated['failure_probability_score'],
                    'total_score' => $total,
                    'risk_level' => $riskLevel,
                    'notes' => $validated['notes'] ?? '',
                ],
                'cr' => [
                    'status' => $cr->status,
                    'status_label' => $cr->status_label,
                    'status_badge' => $cr->status_badge,
                ],
            ], 200);
        }
        
        return redirect()->back()->with('success', $successMsg);
    }
}
