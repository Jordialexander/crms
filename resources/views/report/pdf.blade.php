<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Change Request</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #1f2937; }
        .header { border-bottom: 2px solid #1e2a3a; padding-bottom: 8px; margin-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; margin: 0; }
        .subtitle { font-size: 10px; color: #6b7280; margin: 2px 0 0; }
        .filters { font-size: 9px; color: #374151; margin-top: 6px; }
        .pill { display: inline-block; padding: 2px 6px; border-radius: 10px; background: #f3f4f6; margin: 0 6px 4px 0; }

        .grid { width: 100%; }
        .grid td { vertical-align: top; }

        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; }
        .box h3 { margin: 0 0 6px; font-size: 11px; }
        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 3px 0; border-bottom: 1px solid #f3f4f6; }
        .kv td:first-child { width: 60%; color: #6b7280; }
        .kv tr:last-child td { border-bottom: none; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1e2a3a; color: #fff; padding: 7px 6px; text-align: left; font-size: 9px; }
        td { padding: 6px 6px; border-bottom: 1px solid #e5e7eb; font-size: 9px; vertical-align: top; }
        tr:nth-child(even) td { background: #f9fafb; }
        .muted { color: #6b7280; }

        .badge { display:inline-block; padding: 2px 6px; border-radius: 4px; color: #fff; font-size: 9px; }
        .bg-low { background: #198754; }
        .bg-medium { background: #ffc107; color:#111827; }
        .bg-high { background: #dc3545; }
        .bg-status-success { background: #198754; }
        .bg-status-danger { background: #dc3545; }
        .bg-status-warning { background: #f59e0b; color:#111827; }
        .bg-status-default { background: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Laporan Change Request</p>
        <p class="subtitle">Digenerate pada: {{ $generatedAt }} | Total data: {{ $changeRequests->count() }} CR</p>
        @if(isset($filters))
        <div class="filters">
            <span class="pill">Status: {{ $filters['status'] ?: 'Semua' }}</span>
            <span class="pill">Risk: {{ $filters['risk_level'] ?: 'Semua' }}</span>
            <span class="pill">Type: {{ $filters['change_type'] ?: 'Semua' }}</span>
            <span class="pill">Periode: {{ $filters['date_from'] ?: '-' }} s/d {{ $filters['date_to'] ?: '-' }}</span>
        </div>
        @endif
    </div>

    <table class="grid">
        <tr>
            <td style="width: 34%; padding-right:10px;">
                <div class="box">
                    <h3>Ringkasan Status</h3>
                    <table class="kv">
                        @forelse(($statusCounts ?? []) as $k => $v)
                        <tr>
                            <td>{{ $k }}</td>
                            <td style="text-align:right; font-weight:bold;">{{ $v }}</td>
                        </tr>
                        @empty
                        <tr><td class="muted" colspan="2">-</td></tr>
                        @endforelse
                    </table>
                </div>
            </td>
            <td style="width: 33%; padding-right:10px;">
                <div class="box">
                    <h3>Ringkasan Risiko</h3>
                    <table class="kv">
                        @forelse(($riskCounts ?? []) as $k => $v)
                        <tr>
                            <td>{{ strtoupper($k) }}</td>
                            <td style="text-align:right; font-weight:bold;">{{ $v }}</td>
                        </tr>
                        @empty
                        <tr><td class="muted" colspan="2">-</td></tr>
                        @endforelse
                    </table>
                </div>
            </td>
            <td style="width: 33%;">
                <div class="box">
                    <h3>Ringkasan Tipe Change</h3>
                    <table class="kv">
                        @forelse(($typeCounts ?? []) as $k => $v)
                        <tr>
                            <td>{{ strtoupper($k) }}</td>
                            <td style="text-align:right; font-weight:bold;">{{ $v }}</td>
                        </tr>
                        @empty
                        <tr><td class="muted" colspan="2">-</td></tr>
                        @endforelse
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">CR</th>
                <th style="width: 20%;">Judul</th>
                <th style="width: 7%;">Tipe</th>
                <th style="width: 9%;">Kategori</th>
                <th style="width: 7%;">Prioritas</th>
                <th style="width: 7%;">Risiko</th>
                <th style="width: 9%;">Status</th>
                <th style="width: 13%;">Requester</th>
                <th style="width: 13%;">PIC</th>
                <th style="width: 7%;">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($changeRequests as $cr)
            <tr>
                <td>{{ $cr->cr_number }}</td>
                <td>
                    <div style="font-weight:bold;">{{ \Illuminate\Support\Str::limit($cr->title, 70) }}</div>
                    <div class="muted">Terdampak: {{ \Illuminate\Support\Str::limit($cr->affected_service, 60) }}</div>
                    @if($cr->schedule?->planned_start)
                    <div class="muted">Jadwal: {{ $cr->schedule->planned_start->format('d/m/y H:i') }}</div>
                    @endif
                    @php
                        $note = $cr->closing_note ?: ($cr->rejection_note ?: ($cr->cancellation_note ?: $cr->post_mortem_note));
                    @endphp
                    @if($note)
                    <div class="muted" style="margin-top:4px; font-style:italic;">Catatan: {{ \Illuminate\Support\Str::limit($note, 80) }}</div>
                    @endif
                </td>
                <td>{{ strtoupper($cr->change_type) }}</td>
                <td>{{ $cr->category ? ucfirst($cr->category) : '-' }}</td>
                <td>{{ strtoupper($cr->priority) }}</td>
                <td><span class="badge bg-{{ $cr->risk_level }}">{{ strtoupper($cr->risk_level) }}</span></td>
                @php
                    $statusBg = match($cr->status) {
                        'completed','approved' => 'status-success',
                        'rejected','failed','rollback' => 'status-danger',
                        'under_review','in_progress','scheduled','submitted' => 'status-warning',
                        default => 'status-default',
                    };
                @endphp
                <td><span class="badge bg-{{ $statusBg }}">{{ $cr->status_label }}</span></td>
                <td>{{ $cr->requester?->name ?? '-' }}</td>
                <td>{{ $cr->pic?->name ?? '-' }}</td>
                <td>{{ $cr->created_at?->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

