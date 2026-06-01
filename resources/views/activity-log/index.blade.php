@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-clock-history me-2 text-info"></i>Timeline Aktivitas</h4>
        <p class="text-muted mb-0 small">Semua aktivitas dan perubahan CR tercatat di sini.</p>
    </div>
</div>

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Nomor CR</label>
                <input type="text" name="cr_number" class="form-control form-control-sm" placeholder="CR-2026-..." value="{{ request('cr_number') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Tipe Aktivitas</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(str_replace('_',' ',$type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('activity-log.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Timeline list --}}
@if($logs->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
    Tidak ada aktivitas ditemukan.
</div>
@else
<div class="timeline-global">
    @php $lastDate = null; @endphp
    @foreach($logs as $log)
    @php
        $dateLabel = $log->created_at->format('d M Y');
        $tl = match($log->type) {
            'created'             => ['icon'=>'plus-circle-fill',    'color'=>'secondary', 'label'=>'Dibuat'],
            'updated'             => ['icon'=>'pencil-fill',          'color'=>'secondary', 'label'=>'Diedit'],
            'submitted'           => ['icon'=>'send-check-fill',      'color'=>'primary',   'label'=>'Disubmit'],
            'reviewed'            => ['icon'=>'eye-fill',             'color'=>'warning',   'label'=>'Direview'],
            'approved_step'       => ['icon'=>'check-circle-fill',    'color'=>'primary',   'label'=>'Approved (step)'],
            'approved'            => ['icon'=>'check-circle-fill',    'color'=>'success',   'label'=>'Approved'],
            'rejected'            => ['icon'=>'x-circle-fill',        'color'=>'danger',    'label'=>'Ditolak'],
            'scheduled'           => ['icon'=>'calendar-check-fill',  'color'=>'info',      'label'=>'Dijadwalkan'],
            'rescheduled'         => ['icon'=>'arrow-clockwise',      'color'=>'warning',   'label'=>'Reschedule'],
            'implementation_done' => ['icon'=>'clipboard-check-fill', 'color'=>'success',   'label'=>'Implementasi Selesai'],
            'closed'              => ['icon'=>'lock-fill',            'color'=>'secondary', 'label'=>'Ditutup'],
            default               => ['icon'=>'circle-fill',          'color'=>'secondary', 'label'=>ucfirst(str_replace('_',' ',$log->type))],
        };
    @endphp

    {{-- Date separator --}}
    @if($dateLabel !== $lastDate)
    <div class="d-flex align-items-center gap-3 my-3">
        <div class="border-top flex-grow-1"></div>
        <span class="badge bg-light text-secondary border fw-semibold px-3 py-2" style="font-size:0.75rem">{{ $dateLabel }}</span>
        <div class="border-top flex-grow-1"></div>
    </div>
    @php $lastDate = $dateLabel; @endphp
    @endif

    <div class="card border-0 shadow-sm mb-2 timeline-entry">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-start gap-3">
                {{-- Icon --}}
                <div class="flex-shrink-0 mt-1">
                    <div class="rounded-circle bg-{{ $tl['color'] }} bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px">
                        <i class="bi bi-{{ $tl['icon'] }} text-{{ $tl['color'] }}" style="font-size:1rem"></i>
                    </div>
                </div>
                {{-- Content --}}
                <div class="flex-grow-1" style="min-width:0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="badge bg-{{ $tl['color'] }} bg-opacity-75 text-white" style="font-size:0.7rem">{{ $tl['label'] }}</span>
                        <a href="{{ route('activity-log.show', $log->changeRequest) }}" class="fw-semibold text-decoration-none small">
                            {{ $log->changeRequest->cr_number }}
                        </a>
                        <span class="text-muted small text-truncate">{{ $log->changeRequest->title }}</span>
                    </div>
                    <div class="small text-muted mb-1">{{ $log->description }}</div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-fill text-muted" style="font-size:0.7rem"></i>
                        <span style="font-size:0.75rem" class="text-muted">{{ $log->user->name }}</span>
                        <span class="text-muted" style="font-size:0.7rem">·</span>
                        <span style="font-size:0.75rem" class="text-muted">{{ $log->created_at->format('H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-3">{{ $logs->links() }}</div>
@endif
@endsection
