@props(['title'=>''])
<div class="card info-panel border-0 shadow-sm">
    <div class="card-body p-3">
        <h6 class="text-muted fw-semibold mb-2">{{ $title }}</h6>
        <div class="info-panel-content">{{ $slot }}</div>
    </div>
</div>
