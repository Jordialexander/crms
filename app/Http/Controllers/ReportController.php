<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ChangeRequestsExport;

class ReportController extends Controller
{
    private function buildQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user  = Auth::user();
        $query = ChangeRequest::with(['requester', 'approver', 'pic', 'schedule', 'activityLogs.user']);
        $this->applyVisibilityScope($query, $user);
        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('risk_level'))  $query->where('risk_level', $request->risk_level);
        if ($request->filled('change_type')) $query->where('change_type', $request->change_type);
        if ($request->filled('date_from'))   $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))     $query->whereDate('created_at', '<=', $request->date_to);
        return $query;
    }

    public function index(Request $request)
    {
        $this->authorize('view report');
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $changeRequests = $this->buildQuery($request)->latest()->paginate(20)->withQueryString();

        $baseQuery = ChangeRequest::query();
        $this->applyVisibilityScope($baseQuery, $user);
        $summary = [
            'total'     => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('status','completed')->count(),
            'failed'    => (clone $baseQuery)->whereIn('status',['failed','rollback'])->count(),
            'rejected'  => (clone $baseQuery)->where('status','rejected')->count(),
        ];
        return view('report.index', [
            'title'          => 'Laporan',
            'breadcrumbs'    => ['Dashboard'=>route('dashboard'),'Laporan'=>'#'],
            'changeRequests' => $changeRequests,
            'summary'        => $summary,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('export report');
        $query = $this->buildQuery($request);
        $changeRequests = $query->latest()->get();

        $generatedAt = now()->format('d-m-Y H:i');
        $filters = [
            'status'      => $request->string('status')->toString(),
            'risk_level'  => $request->string('risk_level')->toString(),
            'change_type' => $request->string('change_type')->toString(),
            'date_from'   => $request->string('date_from')->toString(),
            'date_to'     => $request->string('date_to')->toString(),
        ];

        $statusCounts = $changeRequests->groupBy('status_label')->map->count()->sortKeys();
        $riskCounts   = $changeRequests->groupBy('risk_level')->map->count()->sortKeys();
        $typeCounts   = $changeRequests->groupBy('change_type')->map->count()->sortKeys();

        $pdf = Pdf::loadView('report.pdf', [
            'changeRequests' => $changeRequests,
            'generatedAt'    => $generatedAt,
            'filters'        => $filters,
            'statusCounts'   => $statusCounts,
            'riskCounts'     => $riskCounts,
            'typeCounts'     => $typeCounts,
        ])->setPaper('a4', 'landscape');
        return $pdf->download('laporan-cr-'.now()->format('Ymd').'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('export report');
        $generatedAt = now();
        $filters = [
            'status'      => $request->string('status')->toString(),
            'risk_level'  => $request->string('risk_level')->toString(),
            'change_type' => $request->string('change_type')->toString(),
            'date_from'   => $request->string('date_from')->toString(),
            'date_to'     => $request->string('date_to')->toString(),
        ];

        return Excel::download(
            new ChangeRequestsExport($this->buildQuery($request), $filters, $generatedAt),
            'laporan-cr-'.now()->format('Ymd').'.xlsx'
        );
    }
}
