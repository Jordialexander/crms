@extends('layouts.app')

@push('css')
<style>
  /* ── Page ─────────────────────────────────────── */
  .rp-page-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-bottom: .15rem; }
  .rp-page-sub   { font-size: .78rem; color: #94a3b8; }

  /* ── KPI Row ──────────────────────────────────── */
  .rp-kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; margin-bottom: .75rem; }
  .rp-kpi-card {
    background: #fff; border-radius: .6rem; border: 1px solid #e8ecf2;
    padding: .9rem 1.1rem; display: flex; align-items: center; gap: .85rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.02);
  }
  .rp-kpi-icon {
    width: 40px; height: 40px; border-radius: .5rem;
    display: grid; place-items: center; flex-shrink: 0; font-size: 1.1rem;
  }
  .rp-kpi-val   { font-size: 1.5rem; font-weight: 700; line-height: 1; color: #0f172a; }
  .rp-kpi-label { font-size: .72rem; color: #94a3b8; margin-top: .15rem; font-weight: 500; text-transform: uppercase; letter-spacing: .04em; }

  /* ── Filter Card ──────────────────────────────── */
  .rp-filter {
    background: #fff; border-radius: .6rem; border: 1px solid #e8ecf2;
    padding: .85rem 1.1rem; margin-bottom: .75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.02);
  }
  .rp-filter-row { display: flex; gap: .6rem; align-items: flex-end; flex-wrap: wrap; }
  .rp-filter-group { display: flex; flex-direction: column; gap: .25rem; }
  .rp-filter-group label { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; }
  .rp-filter-group select,
  .rp-filter-group input[type="date"] {
    height: 32px; padding: 0 .6rem; border-radius: .4rem;
    border: 1px solid #e2e8f0; font-size: .78rem; color: #334155;
    background: #fafbfd; outline: none;
    transition: border-color .15s;
  }
  .rp-filter-group select:focus,
  .rp-filter-group input[type="date"]:focus { border-color: #2563eb; background: #fff; }
  .rp-filter-actions { display: flex; gap: .4rem; align-items: flex-end; }

  /* ── Expand log row ───────────────────────────── */
  .rp-expand-btn {
    width: 22px; height: 22px; border-radius: .3rem;
    border: 1px solid #e2e8f0; background: #f8fafc;
    display: grid; place-items: center; cursor: pointer;
    transition: border-color .15s, background .15s; flex-shrink: 0;
  }
  .rp-expand-btn:hover { border-color: #2563eb; background: #eff6ff; }
  .rp-expand-btn i { font-size: .65rem; color: #64748b; transition: transform .2s; }
  .rp-expand-btn.open i { transform: rotate(180deg); }

  .rp-log-row td { padding: 0 !important; border: none !important; }
  .rp-log-inner {
    display: none;
    padding: .75rem 1rem .75rem 2.5rem;
    background: #f8fafc; border-top: 1px solid #f1f5f9;
  }
  .rp-log-inner.show { display: block; }
  .rp-log-title { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-bottom: .5rem; }
  .rp-log-list  { display: flex; flex-direction: column; gap: .35rem; }
  .rp-log-item  { display: flex; gap: .6rem; align-items: flex-start; }
  .rp-log-icon  { font-size: .8rem; margin-top: .05rem; flex-shrink: 0; width: 16px; text-align: center; }
  .rp-log-body  { font-size: .76rem; color: #334155; }
  .rp-log-meta  { font-size: .68rem; color: #94a3b8; }

  /* ── Export buttons ───────────────────────────── */
  .btn-export-pdf {
    font-size: .71rem; padding: .28rem .75rem; border-radius: .35rem; font-weight: 600;
    border: 1px solid #fca5a5; background: #fef2f2; color: #dc2626; cursor: pointer;
    text-decoration: none; display: inline-flex; align-items: center; gap: .3rem;
    transition: border-color .15s, background .15s;
  }
  .btn-export-pdf:hover { border-color: #dc2626; background: #fee2e2; color: #dc2626; }

  .btn-export-excel {
    font-size: .71rem; padding: .28rem .75rem; border-radius: .35rem; font-weight: 600;
    border: 1px solid #86efac; background: #f0fdf4; color: #16a34a; cursor: pointer;
    text-decoration: none; display: inline-flex; align-items: center; gap: .3rem;
    transition: border-color .15s, background .15s;
  }
  .btn-export-excel:hover { border-color: #16a34a; background: #dcfce7; color: #16a34a; }

  /* ── Clickable row ────────────────────────────── */
  .table-card tbody tr.cr-row { cursor: pointer; }
  .table-card tbody tr.cr-row:hover { background: #f8fafc; }

  /* ── Type / Priority / Risk badges ───────────────*/
  .b-type     { background:#f1f5f9; color:#475569; font-size:.63rem; font-weight:700; padding:.15rem .45rem; border-radius:2rem; letter-spacing:.03em; }
  .b-risk-low  { background:#f0fdf4; color:#16a34a; }
  .b-risk-med  { background:#fffbeb; color:#d97706; }
  .b-risk-high { background:#fef2f2; color:#dc2626; }
  .b-pri-low   { background:#f1f5f9; color:#64748b; }
  .b-pri-med   { background:#f0f9ff; color:#0284c7; }
  .b-pri-high  { background:#fffbeb; color:#d97706; }
  .b-pri-crit  { background:#fef2f2; color:#dc2626; }
  .b-type, .b-risk-low, .b-risk-med, .b-risk-high,
  .b-pri-low, .b-pri-med, .b-pri-high, .b-pri-crit {
    font-size:.63rem; font-weight:700; padding:.15rem .5rem; border-radius:2rem; letter-spacing:.03em;
    display:inline-block; white-space:nowrap;
  }

  @media (max-width: 1200px) { .rp-kpi-row { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 768px)  { .rp-filter-row { flex-direction: column; } .rp-filter-group { width: 100%; } }
</style>
@endpush

@section('content')

{{-- ── Page Header ───────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.25rem;">
  <div>
    <div class="rp-page-title">Laporan Change Request</div>
    <div class="rp-page-sub">Ekspor dan analisis data change request</div>
  </div>
  @can('export report')
  <div style="display:flex;gap:.5rem;">
    <a href="{{ route('report.pdf', request()->query()) }}" class="btn-export-pdf">
      <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </a>
    <a href="{{ route('report.excel', request()->query()) }}" class="btn-export-excel">
      <i class="bi bi-file-earmark-excel"></i> Export Excel
    </a>
  </div>
  @endcan
</div>

{{-- ── KPI Row ───────────────────────────────────────────────────────────── --}}
@php
  $kpiCards = [
    ['label'=>'Total CR',       'val'=>$summary['total'],     'icon'=>'file-earmark-text', 'bg'=>'#eff6ff', 'color'=>'#2563eb'],
    ['label'=>'Selesai',        'val'=>$summary['completed'], 'icon'=>'check-circle',      'bg'=>'#f0fdf4', 'color'=>'#16a34a'],
    ['label'=>'Gagal / Rollback','val'=>$summary['failed'],   'icon'=>'exclamation-circle','bg'=>'#fef2f2', 'color'=>'#dc2626'],
    ['label'=>'Ditolak',        'val'=>$summary['rejected'],  'icon'=>'x-circle',          'bg'=>'#fef9c3', 'color'=>'#ca8a04'],
  ];
@endphp
<div class="rp-kpi-row">
  @foreach($kpiCards as $k)
  <div class="rp-kpi-card">
    <div class="rp-kpi-icon" style="background:{{ $k['bg'] }}">
      <i class="bi bi-{{ $k['icon'] }}" style="color:{{ $k['color'] }}"></i>
    </div>
    <div>
      <div class="rp-kpi-val">{{ $k['val'] }}</div>
      <div class="rp-kpi-label">{{ $k['label'] }}</div>
    </div>
  </div>
  @endforeach
</div>

{{-- ── Filter ────────────────────────────────────────────────────────────── --}}
<div class="rp-filter">
  <form method="GET">
    <div class="rp-filter-row">
      <div class="rp-filter-group" style="min-width:150px;">
        <label>Status</label>
        <select name="status">
          <option value="">Semua Status</option>
          @foreach([
            'draft'            => 'Draft',
            'need_review'      => 'Need Review',
            'under_review'     => 'Under Review',
            'waiting_approval' => 'Waiting Approval',
            'approved'         => 'Approved',
            'rejected'         => 'Rejected',
            'scheduled'        => 'Scheduled',
            'in_progress'      => 'In Progress',
            'completed'        => 'Completed',
            'failed'           => 'Failed',
            'rollback'         => 'Rollback',
            'closed'           => 'Closed',
          ] as $val => $label)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="rp-filter-group" style="min-width:120px;">
        <label>Level Risiko</label>
        <select name="risk_level">
          <option value="">Semua</option>
          <option value="low"    {{ request('risk_level') === 'low'    ? 'selected' : '' }}>Low</option>
          <option value="medium" {{ request('risk_level') === 'medium' ? 'selected' : '' }}>Medium</option>
          <option value="high"   {{ request('risk_level') === 'high'   ? 'selected' : '' }}>High</option>
        </select>
      </div>

      <div class="rp-filter-group" style="min-width:130px;">
        <label>Tipe Change</label>
        <select name="change_type">
          <option value="">Semua</option>
          <option value="standard"  {{ request('change_type') === 'standard'  ? 'selected' : '' }}>Standard</option>
          <option value="normal"    {{ request('change_type') === 'normal'    ? 'selected' : '' }}>Normal</option>
          <option value="emergency" {{ request('change_type') === 'emergency' ? 'selected' : '' }}>Emergency</option>
        </select>
      </div>

      <div class="rp-filter-group">
        <label>Dari Tanggal</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}">
      </div>

      <div class="rp-filter-group">
        <label>Sampai Tanggal</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}">
      </div>

      <div class="rp-filter-actions">
        <button type="submit" class="btn-xs" style="background:#2563eb;color:#fff;border-color:#2563eb;">
          <i class="bi bi-funnel"></i> Filter
        </button>
        <a href="{{ route('report.index') }}" class="btn-xs">
          <i class="bi bi-x"></i> Reset
        </a>
      </div>
    </div>
  </form>
</div>

{{-- ── Table ─────────────────────────────────────────────────────────────── --}}
@php
  $logIconMap = [
    'submitted'           => ['icon'=>'send-check',       'color'=>'#0284c7'],
    'reviewed'            => ['icon'=>'eye',               'color'=>'#d97706'],
    'review_started'      => ['icon'=>'eye',               'color'=>'#d97706'],
    'approved_step'       => ['icon'=>'check-circle',      'color'=>'#2563eb'],
    'approved'            => ['icon'=>'check-circle-fill', 'color'=>'#16a34a'],
    'rejected'            => ['icon'=>'x-circle',          'color'=>'#dc2626'],
    'scheduled'           => ['icon'=>'calendar-check',    'color'=>'#2563eb'],
    'rescheduled'         => ['icon'=>'calendar-event',    'color'=>'#d97706'],
    'implementation_done' => ['icon'=>'clipboard-check',   'color'=>'#16a34a'],
    'closed'              => ['icon'=>'lock',              'color'=>'#64748b'],
  ];
  $riskCls = ['low'=>'b-risk-low','medium'=>'b-risk-med','high'=>'b-risk-high'];
  $priCls  = ['low'=>'b-pri-low','medium'=>'b-pri-med','high'=>'b-pri-high','critical'=>'b-pri-crit'];
@endphp

<div class="table-card">
  <div class="tc-header">
    <span style="font-size:.82rem;font-weight:700;color:#0f172a;">Daftar Change Request</span>
    <span style="font-size:.72rem;color:#94a3b8;">{{ $changeRequests->total() }} CR ditemukan</span>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th style="width:36px;"></th>
          <th>CR Number</th>
          <th>Judul</th>
          <th>Tipe</th>
          <th>Prioritas</th>
          <th>Risiko</th>
          <th>Status</th>
          <th>Requester</th>
          <th>Tanggal</th>
        </tr>
      </thead>
      <tbody>
        @forelse($changeRequests as $cr)
        {{-- Main row --}}
        <tr class="cr-row" onclick="window.location='{{ route('cr.show', $cr) }}'" title="Buka {{ $cr->cr_number }}">
          <td onclick="event.stopPropagation()">
            @if($cr->activityLogs->count() > 0)
            <button class="rp-expand-btn" type="button" data-target="log-{{ $cr->id }}" title="Lihat log aktivitas">
              <i class="bi bi-chevron-down"></i>
            </button>
            @endif
          </td>
          <td><span class="cr-number">{{ $cr->cr_number }}</span></td>
          <td style="max-width:200px;" class="cr-title text-truncate" title="{{ $cr->title }}">{{ $cr->title }}</td>
          <td><span class="b-type">{{ strtoupper($cr->change_type) }}</span></td>
          <td><span class="{{ $priCls[$cr->priority] ?? 'b-pri-low' }}">{{ strtoupper($cr->priority) }}</span></td>
          <td><span class="{{ $riskCls[$cr->risk_level] ?? 'b-type' }}">{{ strtoupper($cr->risk_level ?? '-') }}</span></td>
          @php $rBadge = $cr->status === 'closed' && $cr->closed_reason ? 'badge-closed_'.$cr->closed_reason : 'badge-'.($cr->status ?? 'unknown'); @endphp
          <td><span class="s-badge {{ $rBadge }}">{{ $cr->status_label ?? ucfirst(str_replace('_',' ',$cr->status)) }}</span></td>
          <td class="cr-title">{{ $cr->requester?->name ?? '—' }}</td>
          <td class="cr-date">{{ $cr->created_at->format('d M Y') }}</td>
        </tr>

        {{-- Expandable log row --}}
        @if($cr->activityLogs->count() > 0)
        <tr class="rp-log-row">
          <td colspan="9">
            <div class="rp-log-inner" id="log-{{ $cr->id }}">
              <div class="rp-log-title"><i class="bi bi-activity me-1"></i>Log Aktivitas — {{ $cr->cr_number }}</div>
              <div class="rp-log-list">
                @foreach($cr->activityLogs->sortBy('created_at') as $log)
                @php $lm = $logIconMap[$log->type] ?? ['icon'=>'circle','color'=>'#94a3b8']; @endphp
                <div class="rp-log-item">
                  <i class="bi bi-{{ $lm['icon'] }} rp-log-icon" style="color:{{ $lm['color'] }}"></i>
                  <div>
                    <div class="rp-log-body">
                      <strong>{{ $log->user?->name ?? '—' }}</strong>
                      <span class="rp-log-meta ms-1">{{ $log->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="rp-log-meta">{{ $log->description }}</div>
                  </div>
                </div>
                @endforeach
              </div>
            </div>
          </td>
        </tr>
        @endif

        @empty
        <tr>
          <td colspan="9" style="text-align:center;padding:3rem 1rem;color:#94a3b8;">
            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
            Tidak ada data change request
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($changeRequests->hasPages())
  <div style="padding:.75rem 1rem;border-top:1px solid #f1f5f9;background:#fff;">
    {{ $changeRequests->links() }}
  </div>
  @endif
</div>

@endsection

@push('js')
<script>
(function () {
  document.querySelectorAll('.rp-expand-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.getElementById(btn.dataset.target);
      if (!target) return;
      var isOpen = target.classList.contains('show');
      target.classList.toggle('show', !isOpen);
      btn.classList.toggle('open', !isOpen);
    });
  });
})();
</script>
@endpush
