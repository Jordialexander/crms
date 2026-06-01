<?php

namespace App\Exports\Sheets;

use App\Models\ChangeRequest;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ChangeRequestsSheet implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    public function __construct(
        private readonly Builder $query,
        private readonly array $filters,
        private readonly CarbonInterface $generatedAt,
    ) {}

    public function query()
    {
        return (clone $this->query)->with(['requester', 'approver', 'pic']);
    }

    public function headings(): array
    {
        return [
            'No. CR',
            'Judul',
            'Tipe',
            'Kategori',
            'Prioritas',
            'Risiko',
            'Status',
            'Requester',
            'Approver',
            'PIC',
            'Tanggal Mulai (Plan)',
            'Tanggal Selesai (Plan)',
            'Estimasi Downtime (Menit)',
            'Dibuat Pada',
            'Disubmit Pada',
            'Disetujui Pada',
            'Ditutup Pada',
            'Layanan Terdampak',
            'Alasan',
            'Dampak',
            'Rollback Plan',
            'Catatan',
        ];
    }

    /**
     * @param  ChangeRequest  $cr
     */
    public function map($cr): array
    {
        $note = '-';
        if ($cr->closing_note) $note = $cr->closing_note;
        elseif ($cr->rejection_note) $note = $cr->rejection_note;
        elseif ($cr->cancellation_note) $note = $cr->cancellation_note;
        elseif ($cr->post_mortem_note) $note = $cr->post_mortem_note;

        return [
            $cr->cr_number,
            $cr->title,
            strtoupper($cr->change_type),
            $cr->category ? ucfirst($cr->category) : '-',
            strtoupper($cr->priority),
            strtoupper($cr->risk_level),
            $cr->status_label,
            $cr->requester?->name ?? '-',
            $cr->approver?->name ?? '-',
            $cr->pic?->name ?? '-',
            optional($cr->schedule?->planned_start)?->format('Y-m-d H:i') ?? '-',
            optional($cr->schedule?->planned_end)?->format('Y-m-d H:i') ?? '-',
            $cr->schedule?->estimated_downtime_minutes ?? '0',
            optional($cr->created_at)?->format('Y-m-d H:i') ?? '-',
            optional($cr->submitted_at)?->format('Y-m-d H:i') ?? '-',
            optional($cr->approved_at)?->format('Y-m-d H:i') ?? '-',
            optional($cr->closed_at)?->format('Y-m-d H:i') ?? '-',
            $cr->affected_service,
            Str::of($cr->reason)->squish()->toString(),
            Str::of((string) $cr->impact)->squish()->toString(),
            Str::of($cr->rollback_plan)->squish()->toString(),
            Str::of($note)->squish()->toString(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');

        $sheet->getStyle('A1:V1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E2A3A'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle('B:B')->getAlignment()->setWrapText(true);
        $sheet->getStyle('R:V')->getAlignment()->setWrapText(true);

        $sheet->getStyle('A:V')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'K' => 'yyyy-mm-dd hh:mm',
            'L' => 'yyyy-mm-dd hh:mm',
            'N' => 'yyyy-mm-dd hh:mm',
            'O' => 'yyyy-mm-dd hh:mm',
            'P' => 'yyyy-mm-dd hh:mm',
            'Q' => 'yyyy-mm-dd hh:mm',
        ];
    }
}
