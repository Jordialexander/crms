@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ $cr->cr_number }}</h4>
        <p class="text-muted mb-0">{{ $cr->title }}</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-{{ $cr->status_badge }} fs-6 align-self-center">{{ $cr->status_label }}</span>
        <a href="{{ route('approval.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
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
                    <span class="badge bg-{{ $cr->risk_badge }}">RISK: {{ strtoupper($cr->risk_level) }}</span>
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
                    @if($cr->rejection_note)
                    <div class="col-12">
                        <small class="text-danger fw-semibold">Alasan Penolakan</small>
                        <div class="mt-1 p-2 bg-danger bg-opacity-10 border border-danger rounded text-danger">{{ $cr->rejection_note }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Risk Assessment --}}
        @if($cr->riskAssessment)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-shield-exclamation me-2 text-warning"></i>Risk Assessment
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach([
                        ['label'=>'Dampak Layanan','score'=>$cr->riskAssessment->impact_score],
                        ['label'=>'Kompleksitas Teknis','score'=>$cr->riskAssessment->complexity_score],
                        ['label'=>'Jumlah User Terdampak','score'=>$cr->riskAssessment->user_impact_score],
                        ['label'=>'Kemungkinan Gagal','score'=>$cr->riskAssessment->failure_probability_score],
                    ] as $item)
                    <div class="col-md-6">
                        <small class="text-muted">{{ $item['label'] }}</small>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <div class="progress flex-grow-1" style="height:8px">
                                <div class="progress-bar bg-{{ $item['score'] <= 2 ? 'success' : ($item['score'] <= 3 ? 'warning' : 'danger') }}" style="width:{{ $item['score'] * 20 }}%"></div>
                            </div>
                            <strong>{{ $item['score'] }}/5</strong>
                        </div>
                    </div>
                    @endforeach
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded">
                            <div class="text-center">
                                <div class="fs-3 fw-bold">{{ $cr->riskAssessment->total_score }}</div>
                                <small class="text-muted">Total Skor</small>
                            </div>
                            <div class="vr"></div>
                            <div>
                                <strong>Level Risiko: </strong>
                                <span class="badge bg-{{ $cr->risk_badge }} fs-6">{{ strtoupper($cr->riskAssessment->risk_level) }}</span>
                                <div class="small text-muted mt-1">5–8 = Low | 9–14 = Medium | 15–20 = High</div>
                            </div>
                        </div>
                    </div>
                    @if($cr->riskAssessment->notes)
                    <div class="col-12">
                        <small class="text-muted fw-semibold">Catatan Risk Assessment</small>
                        <div class="mt-1">{{ $cr->riskAssessment->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Approval History --}}
        @if($cr->approvals->count() > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2 text-secondary"></i>Riwayat Approval</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Level</th><th>Approver</th><th>Status</th><th>Catatan</th><th>Tanggal</th></tr></thead>
                    <tbody>
                        @foreach($cr->approvals->sortBy('step') as $appr)
                        <tr>
                            <td><span class="badge bg-secondary">Level {{ $appr->step }}</span></td>
                            <td>{{ $appr->approver->name ?? '-' }}
                                @if($cr->current_approval_step == $appr->step && $cr->status === 'waiting_approval')
                                <span class="badge bg-primary ms-1">Aktif</span>
                                @endif
                            </td>
                            <td><span class="badge bg-{{ match($appr->status) { 'approved'=>'success','rejected'=>'danger','submitted'=>'info',default=>'secondary'} }}">{{ strtoupper($appr->status) }}</span></td>
                            <td>{{ $appr->note ?? '-' }}</td>
                            <td class="text-muted small">{{ $appr->approved_at?->format('d M Y H:i') ?? '-' }}</td>
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
                    <div class="fw-semibold">{{ $cr->currentApprover->name ?? ($cr->approver->name ?? '-') }}</div>
                </div>
                @if($cr->approved_at)
                <div class="mb-2">
                    <small class="text-muted">Diapprove</small>
                    <div>{{ $cr->approved_at->format('d M Y H:i') }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Approval Action --}}
        @if($cr->status === 'waiting_approval' && $cr->current_approver_id === auth()->id())
        @can('approve change_request')
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold text-success">
                <i class="bi bi-check2-circle me-2"></i>Tindakan Approval
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('approval.approve', $cr) }}" class="mb-3">
                    @csrf
                    <label class="form-label small fw-semibold">Catatan Persetujuan</label>
                    <textarea name="note" class="form-control form-control-sm mb-2" rows="3" placeholder="Opsional..."></textarea>
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Setujui CR ini?')">
                        <i class="bi bi-check-circle me-1"></i> Approve
                    </button>
                </form>
                <hr>
                <form method="POST" action="{{ route('approval.reject', $cr) }}">
                    @csrf
                    <label class="form-label small fw-semibold text-danger">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="note" class="form-control form-control-sm mb-2" rows="3" placeholder="Jelaskan alasan penolakan..." required></textarea>
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Tolak CR ini?')">
                        <i class="bi bi-x-circle me-1"></i> Reject
                    </button>
                </form>
            </div>
        </div>
        @endcan
        @endif

        {{-- Timeline --}}
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
