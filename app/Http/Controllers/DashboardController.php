<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\ChangeSchedule;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user  = Auth::user();
        $query = ChangeRequest::query();
        $this->applyVisibilityScope($query, $user);
        $stats = [
            'total'       => (clone $query)->count(),
            'pending'     => (clone $query)->whereIn('status',['submitted','under_review'])->count(),
            'approved'    => (clone $query)->where('status','approved')->count(),
            'rejected'    => (clone $query)->where('status','rejected')->count(),
            'in_progress' => (clone $query)->where('status','in_progress')->count(),
            'completed'   => (clone $query)->where('status','closed')->where('closed_reason','completed')->count(),
            'failed'      => (clone $query)->whereIn('status',['failed','rollback'])->count(),
            'low_risk'    => (clone $query)->where('risk_level','low')->count(),
            'medium_risk' => (clone $query)->where('risk_level','medium')->count(),
            'high_risk'   => (clone $query)->where('risk_level','high')->count(),
        ];
        $monitoring = [
            'draft'             => (clone $query)->where('status', 'draft')->count(),
            'need_review'       => (clone $query)->where('status', 'need_review')->count(),
            'under_review'      => (clone $query)->where('status', 'under_review')->count(),
            'waiting_approval'  => (clone $query)->where('status', 'waiting_approval')->count(),
            'unscheduled'       => (clone $query)->where('status', 'approved')->count(),
            'scheduled'         => (clone $query)->where('status', 'scheduled')->count(),
            'in_progress'       => (clone $query)->where('status', 'in_progress')->count(),
            'rejected'          => (clone $query)->where('status', 'rejected')->count(),
            'completed'         => (clone $query)->where('status', 'completed')->count(),
            'rollback'          => (clone $query)->where('status', 'rollback')->count(),
            'failed'            => (clone $query)->where('status', 'failed')->count(),
            'closed'            => (clone $query)->where('status', 'closed')->count(),
            'canceled'          => (clone $query)->where('status', 'canceled')->count(),
        ];
        $recentCRs = (clone $query)->with(['requester','approver'])->latest()->take(10)->get();
        $visibleCrIds = (clone $query)->pluck('id');
        $upcomingSchedules = ChangeSchedule::with('changeRequest.requester')
            ->where('planned_start','>=',now())
            ->whereHas('changeRequest', fn($q) => $q
                ->whereIn('id', $visibleCrIds)
                ->whereNotIn('status', ['closed','completed','failed','rollback'])
            )
            ->orderBy('planned_start')->take(5)->get();
        return view('dashboard.index', [
            'title'              => 'Dashboard',
            'breadcrumbs'        => ['Dashboard'=>'#'],
            'stats'              => $stats,
            'monitoring'         => $monitoring,
            'recentCRs'          => $recentCRs,
            'upcomingSchedules'  => $upcomingSchedules,
        ]);
    }
}
