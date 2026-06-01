@props(['title'=>'','value'=>'-','icon'=>'info-circle'])
<div class="stat-card-custom p-3 d-flex align-items-center justify-content-between">
    <div>
        <div class="h4 mb-0 fw-bold">{{ $value }}</div>
        <div class="small text-muted">{{ $title }}</div>
    </div>
    <div class="ms-3 text-accent"><i class="bi bi-{{ $icon }} fs-2"></i></div>
</div>
