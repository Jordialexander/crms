@php
    $typeMap = [
        'created'             => ['cls' => 'secondary', 'label' => 'Created'],
        'updated'             => ['cls' => 'secondary', 'label' => 'Updated'],
        'submitted'           => ['cls' => 'primary',   'label' => 'Submitted'],
        'reviewed'            => ['cls' => 'warning',   'label' => 'Reviewed'],
        'review_started'      => ['cls' => 'info',      'label' => 'Review Started'],
        'risk_assessment'     => ['cls' => 'warning',   'label' => 'Risk Assessment'],
        'review_completed'    => ['cls' => 'success',   'label' => 'Review Completed'],
        'approved_step'       => ['cls' => 'primary',   'label' => 'Approved'],
        'approved'            => ['cls' => 'success',   'label' => 'Approved'],
        'rejected'            => ['cls' => 'danger',    'label' => 'Rejected'],
        'scheduled'           => ['cls' => 'info',      'label' => 'Scheduled'],
        'rescheduled'         => ['cls' => 'warning',   'label' => 'Rescheduled'],
        'implementation_done' => ['cls' => 'success',   'label' => 'Done'],
        'closed'              => ['cls' => 'secondary', 'label' => 'Closed'],
    ];

    $meta = $typeMap[$log->type]
        ?? ['cls' => 'secondary', 'label' => ucwords(str_replace('_', ' ', $log->type))];

    // ── Resolve diff rows ──────────────────────────────────────────
    $diffRows = null;

    if (str_starts_with(trim((string) $log->description), '{')) {
        $decoded = json_decode($log->description, true);
        if (isset($decoded['_type'], $decoded['changes']) && $decoded['_type'] === 'diff') {
            $diffRows = $decoded['changes'];
        }
    }

    if ($log->type === 'risk_assessment' && ! $diffRows) {
        $pattern = '/Dampak=(\d+).*?Kompleksitas=(\d+).*?UserImpact=(\d+).*?FailureProb=(\d+).*?Total=(\d+).*?Level=(\w+)/is';
        if (preg_match($pattern, (string) $log->description, $m)) {
            $prev = null;
            $idx  = $logs->search($log);
            if ($idx !== false) {
                for ($i = $idx - 1; $i >= 0; $i--) {
                    if ($logs[$i]->type === 'risk_assessment'
                        && preg_match($pattern, (string) $logs[$i]->description, $pm)) {
                        $prev = $pm;
                        break;
                    }
                }
            }
            $diffRows = [
                ['field' => 'Dampak Layanan',    'before' => $prev ? $prev[1] : '-', 'after' => $m[1]],
                ['field' => 'Kompleksitas',      'before' => $prev ? $prev[2] : '-', 'after' => $m[2]],
                ['field' => 'User Terdampak',    'before' => $prev ? $prev[3] : '-', 'after' => $m[3]],
                ['field' => 'Kemungkinan Gagal', 'before' => $prev ? $prev[4] : '-', 'after' => $m[4]],
                ['field' => 'Total Skor',        'before' => $prev ? $prev[5] : '-', 'after' => $m[5]],
                ['field' => 'Level Risiko',      'before' => $prev ? strtoupper($prev[6]) : '-', 'after' => strtoupper($m[6])],
            ];
        }
    }
@endphp

<div class="al-item">
    {{-- Stem: dot + vertical line --}}
    <div class="al-stem">
        <div class="al-dot c-{{ $meta['cls'] }}"></div>
        <div class="al-line"></div>
    </div>

    {{-- Card --}}
    <div class="al-card">
        <div class="al-card-head">
            <span class="al-badge al-b-{{ $meta['cls'] }}">{{ $meta['label'] }}</span>
            <span class="al-actor">{{ $log->user->name }}</span>
            <span class="al-time">{{ $log->created_at->format('d M Y, H:i') }}</span>
        </div>

        @if($diffRows)
            <div class="al-diff">
                <table>
                    <thead>
                        <tr>
                            <th class="td-field">Field</th>
                            <th>Sebelum</th>
                            <th>Sesudah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($diffRows as $row)
                        <tr>
                            <td class="td-field">{{ $row['field'] }}</td>
                            <td class="td-before">{{ $row['before'] ?: '-' }}</td>
                            <td class="td-after">{{ $row['after'] ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="al-desc">{{ $log->description }}</div>
        @endif
    </div>
</div>
