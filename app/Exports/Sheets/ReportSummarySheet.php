<?php

namespace App\Exports\Sheets;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportSummarySheet implements FromArray, WithStyles, ShouldAutoSize
{
    public function __construct(
        private readonly Builder $query,
        private readonly array $filters,
        private readonly CarbonInterface $generatedAt,
    ) {}

    public function array(): array
    {
        $rows = (clone $this->query)->get(['id', 'status', 'closed_reason', 'risk_level', 'change_type']);

        $statusCounts = $rows->groupBy('status_label')->map->count()->sortKeys();
        $riskCounts   = $rows->groupBy('risk_level')->map->count()->sortKeys();
        $typeCounts   = $rows->groupBy('change_type')->map->count()->sortKeys();

        $filterText = collect([
            'Status' => $this->filters['status'] ?: 'Semua',
            'Risk Level' => $this->filters['risk_level'] ?: 'Semua',
            'Change Type' => $this->filters['change_type'] ?: 'Semua',
            'Date From' => $this->filters['date_from'] ?: '-',
            'Date To' => $this->filters['date_to'] ?: '-',
        ])->map(fn ($v, $k) => $k . ': ' . $v)->implode(' | ');

        $out = [
            ['Laporan Change Request'],
            ['Generated At', $this->generatedAt->format('Y-m-d H:i:s')],
            ['Filter', $filterText],
            [],
            ['Ringkasan', 'Jumlah'],
            ['Total CR', $rows->count()],
            [],
            ['By Status', 'Jumlah'],
        ];

        foreach ($statusCounts as $k => $v) {
            $out[] = [str_replace('_', ' ', strtoupper((string) $k)), $v];
        }

        $out[] = [];
        $out[] = ['By Risk Level', 'Jumlah'];
        foreach ($riskCounts as $k => $v) {
            $out[] = [strtoupper((string) $k), $v];
        }

        $out[] = [];
        $out[] = ['By Change Type', 'Jumlah'];
        foreach ($typeCounts as $k => $v) {
            $out[] = [strtoupper((string) $k), $v];
        }

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E2A3A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A5:B5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D6EFD']],
        ]);
        $sheet->getStyle('A8:B8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
        ]);

        $sheet->getStyle('A:A')->getAlignment()->setWrapText(true);

        return [];
    }
}

