<?php

namespace App\Http\Controllers;

use App\Models\CrActivityLog;
use App\Models\ChangeRequest;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private const PER_PAGE = 10;

    private function checkAccess(ChangeRequest $cr): void
    {
        $this->authorize('view change_request');

        $user = auth()->user();

        $canAccess = $user->can('view all change_request')
            || $user->can('view team change_request')
            || $cr->requester_id === $user->id
            || $cr->pic_id === $user->id
            || ($cr->approver_chain && in_array($user->id, (array) $cr->approver_chain));

        if (! $canAccess) {
            abort(403, 'Anda tidak memiliki akses ke timeline CR ini.');
        }
    }

    public function show(ChangeRequest $cr)
    {
        $this->checkAccess($cr);

        $cr->load(['requester', 'approver', 'pic']);

        // allLogs = seluruh collection (ringan, hanya teks) untuk lookup diff risk_assessment di partial
        $allLogs = CrActivityLog::with('user')
            ->where('change_request_id', $cr->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // logs = paginated untuk render page 1 saja di server
        $logs = $allLogs->take(self::PER_PAGE);
        $hasMore = $allLogs->count() > self::PER_PAGE;

        return view('activity-log.show', [
            'title'       => 'Timeline: ' . $cr->cr_number,
            'breadcrumbs' => [
                'Dashboard'      => route('dashboard'),
                'Change Request' => route('cr.index'),
                $cr->cr_number   => route('cr.show', $cr),
                'Timeline'       => '#',
            ],
            'cr'      => $cr,
            'logs'    => $logs,
            'allLogs' => $allLogs,
            'hasMore' => $hasMore,
        ]);
    }

    public function loadMore(Request $request, ChangeRequest $cr)
    {
        $this->checkAccess($cr);

        $page = max(1, (int) $request->query('page', 1));

        // Semua log (dengan user) diperlukan agar lookup prev risk_assessment di partial bisa jalan.
        // Ini satu query ringan — hanya teks & metadata, bukan file/blob.
        $allLogs = CrActivityLog::with('user')
            ->where('change_request_id', $cr->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $total     = $allLogs->count();
        $paginated = $allLogs->forPage($page, self::PER_PAGE);
        $hasMore   = ($page * self::PER_PAGE) < $total;

        $html = '';
        foreach ($paginated as $log) {
            $html .= view('activity-log._item', [
                'log'  => $log,
                'logs' => $allLogs,
            ])->render();
        }

        return response()->json(['html' => $html, 'hasMore' => $hasMore]);
    }
}
