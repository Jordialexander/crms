@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ $cr->cr_number }}</h4>
        <p class="text-muted mb-0">{{ $cr->title }}</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-info fs-6 align-self-center">NEED REVIEW</span>
        <a href="{{ route('need-review.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        {{-- Info CR --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                <span><i class="bi bi-info-circle me-2 text-primary"></i>Informasi CR</span>
                <div class="d-flex gap-2">
                    <span class="badge bg-{{ match($cr->priority){ 'low'=>'secondary','medium'=>'info','high'=>'warning','critical'=>'danger',default=>'secondary'} }}">{{ strtoupper($cr->priority) }}</span>
                    <span class="badge bg-secondary">{{ strtoupper($cr->change_type) }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted fw-semibold">Kategori</small>
                        <div>{{ ucfirst($cr->category) }}</div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted fw-semibold">Layanan Terdampak</small>
                        <div>{{ $cr->affected_service }}</div>
                    </div>
                    <div class="col-12">
                        <small class="text-muted fw-semibold">Deskripsi</small>
                        <div class="mt-1">{{ $cr->description }}</div>
                    </div>
                    <div class="col-12">
                        <small class="text-muted fw-semibold">Alasan Perubahan</small>
                        <div class="mt-1">{{ $cr->reason }}</div>
                    </div>
                    @if($cr->impact)
                    <div class="col-12">
                        <small class="text-muted fw-semibold">Potensi Dampak</small>
                        <div class="mt-1">{{ $cr->impact }}</div>
                    </div>
                    @endif
                    <div class="col-12">
                        <small class="text-muted fw-semibold">Rollback Plan</small>
                        <div class="mt-1 p-2 bg-light rounded">{{ $cr->rollback_plan }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Approval Chain --}}
        @if($cr->approvals->count() > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-people me-2 text-secondary"></i>Rantai Approval</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Level</th><th>Approver</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($cr->approvals->sortBy('step') as $appr)
                        <tr>
                            <td><span class="badge bg-secondary">Level {{ $appr->step }}</span></td>
                            <td>{{ $appr->approver->name ?? '-' }}
                                @if($cr->current_approval_step == $appr->step)
                                <span class="badge bg-warning text-dark ms-1">Aktif</span>
                                @endif
                            </td>
                            <td><span class="badge bg-{{ match($appr->status) { 'approved'=>'success','rejected'=>'danger','submitted'=>'info',default=>'secondary'} }}">{{ strtoupper($appr->status) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        {{-- Meta --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person-badge me-2 text-primary"></i>Informasi Pengajuan</div>
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted">Requester</small>
                    <div class="fw-semibold">{{ $cr->requester->name }}</div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Disubmit</small>
                    <div>{{ $cr->submitted_at?->format('d M Y H:i') ?? '-' }}</div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">PIC Implementasi</small>
                    <div>{{ $cr->pic->name ?? '-' }}</div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Approver Saat Ini</small>
                    <div class="fw-semibold text-warning">{{ $cr->currentApprover->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        {{-- Action --}}
        @if($cr->current_approver_id === auth()->id())
        @can('approve change_request')
        <div class="card border-warning shadow-sm mb-3">
            <div class="card-header bg-warning bg-opacity-10 fw-semibold text-warning">
                <i class="bi bi-eye me-2"></i>Tindakan Review
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Anda adalah approver untuk CR ini. Klik tombol di bawah untuk memulai proses review dan mengisi Risk Assessment.
                </p>
                <form method="POST" action="{{ route('need-review.start', $cr) }}" onsubmit="return confirm('Mulai review CR ini?')">
                    @csrf
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="bi bi-play-fill me-1"></i> Mulai Review
                    </button>
                </form>
            </div>
        </div>
        @endcan
        @endif

        {{-- Timeline button --}}
        <a href="{{ route('activity-log.show', $cr) }}" class="btn btn-outline-info w-100 mb-3">
            <i class="bi bi-clock-history me-2"></i>Lihat Timeline Aktivitas
        </a>

        {{-- Attachments --}}
        @if($cr->attachments->count() > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-paperclip me-2"></i>Lampiran</div>
            <div class="card-body">
                @foreach($cr->attachments as $att)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-file-earmark text-muted"></i>
                    <span class="small text-truncate">{{ $att->original_name }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
