<?php

namespace App\Exports;

use App\Exports\Sheets\ChangeRequestsSheet;
use App\Exports\Sheets\ReportSummarySheet;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

class ChangeRequestsExport implements WithMultipleSheets, WithProperties
{
    public function __construct(
        private readonly Builder $query,
        private readonly array $filters,
        private readonly CarbonInterface $generatedAt,
    ) {}

    public function sheets(): array
    {
        return [
            new ReportSummarySheet($this->query, $this->filters, $this->generatedAt),
            new ChangeRequestsSheet($this->query, $this->filters, $this->generatedAt),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'Change Request Management System',
            'title'          => 'Laporan Change Request',
            'description'    => 'Export laporan Change Request - Change Request Management System',
            'lastModifiedBy' => 'Change Request Management System',
        ];
    }
}

