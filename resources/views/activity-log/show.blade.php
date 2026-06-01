@extends('layouts.app')

@push('css')
<style>
  .al-page-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-bottom: .1rem; }
  .al-page-sub   { font-size: .78rem; color: #94a3b8; }

  .al-timeline { max-width: 720px; }

  .al-item {
    display: flex; gap: 1rem;
    padding-bottom: 1.25rem; position: relative;
  }
  .al-item:last-child { padding-bottom: 0; }

  .al-stem {
    display: flex; flex-direction: column; align-items: center; flex-shrink: 0;
    padding-top: .35rem;
  }
  .al-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
    border: 2px solid #fff; box-shadow: 0 0 0 2px currentColor;
  }
  .al-line { width: 1px; flex: 1; background: #e2e8f0; margin-top: .35rem; }
  .al-item:last-child .al-line { display: none; }

  .al-card {
    flex: 1; background: #fff; border-radius: .6rem;
    border: 1px solid #e8ecf2; padding: .85rem 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.02);
  }
  .al-card-head {
    display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
    margin-bottom: .5rem;
  }
  .al-badge {
    font-size: .63rem; font-weight: 700; letter-spacing: .05em;
    padding: .18rem .5rem; border-radius: 2rem;
    display: inline-flex; align-items: center; gap: .25rem;
  }
  .al-badge::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; }
  .al-actor { font-size: .8rem; font-weight: 600; color: #0f172a; }
  .al-time  { font-size: .7rem; color: #94a3b8; margin-left: auto; }
  .al-desc  { font-size: .8rem; color: #475569; line-height: 1.5; }

  .al-diff { margin-top: .6rem; border-radius: .4rem; overflow: hidden; border: 1px solid #f1f5f9; }
  .al-diff table { width: 100%; border-collapse: collapse; }
  .al-diff thead th {
    background: #f8fafc; padding: .4rem .75rem;
    font-size: .65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; color: #94a3b8; border-bottom: 1px solid #f1f5f9;
  }
  .al-diff tbody td {
    padding: .45rem .75rem; font-size: .78rem;
    border-bottom: 1px solid #f8fafc; vertical-align: top;
  }
  .al-diff tbody tr:last-child td { border-bottom: none; }
  .al-diff .td-field  { color: #64748b; width: 28%; }
  .al-diff .td-before { color: #dc2626; text-decoration: line-through; width: 36%; }
  .al-diff .td-after  { color: #16a34a; font-weight: 600; width: 36%; }

  .al-b-secondary { background:#f1f5f9; color:#64748b; }
  .al-b-primary   { background:#eff6ff; color:#2563eb; }
  .al-b-info      { background:#f0f9ff; color:#0284c7; }
  .al-b-warning   { background:#fffbeb; color:#d97706; }
  .al-b-success   { background:#f0fdf4; color:#16a34a; }
  .al-b-danger    { background:#fef2f2; color:#dc2626; }

  .al-dot.c-secondary { color:#94a3b8; }
  .al-dot.c-primary   { color:#2563eb; }
  .al-dot.c-info      { color:#0284c7; }
  .al-dot.c-warning   { color:#d97706; }
  .al-dot.c-success   { color:#16a34a; }
  .al-dot.c-danger    { color:#dc2626; }

  @keyframes shimmer {
    0%   { background-position: -600px 0; }
    100% { background-position:  600px 0; }
  }
  .skel {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 600px 100%;
    animation: shimmer 1.4s infinite linear;
    border-radius: .35rem;
  }
  .skel-item { display: flex; gap: 1rem; padding-bottom: 1.25rem; }
  .skel-dot  { width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-top:.35rem; }
  .skel-card { flex:1; background:#fff; border-radius:.6rem; border:1px solid #e8ecf2; padding:.85rem 1rem; }
  .skel-line { height: .75rem; margin-bottom: .5rem; }

  .al-empty {
    padding: 2.5rem 1rem; text-align: center; color: #94a3b8; font-size: .82rem;
  }
  .al-empty i { font-size: 2rem; display: block; margin-bottom: .5rem; }

  .al-item { animation: fadeUp .22s ease both; }
  @keyframes fadeUp {
    from { opacity:0; transform:translateY(8px); }
    to   { opacity:1; transform:translateY(0); }
  }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.25rem;">
    <div>
        <div class="al-page-title">{{ $cr->cr_number }} &mdash; Timeline Aktivitas</div>
        <div class="al-page-sub">{{ $cr->title }}</div>
    </div>
    <a href="{{ route('cr.show', $cr) }}" class="btn-xs">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="al-timeline" id="al-timeline">
    @if($logs->isEmpty())
        <div class="al-empty">
            <i class="bi bi-clock-history"></i>
            Belum ada aktivitas tercatat untuk CR ini.
        </div>
    @else
        {{-- Hanya render page 1 (10 item). Sisanya di-fetch via AJAX saat scroll. --}}
        @foreach($logs as $log)
            @include('activity-log._item', ['log' => $log, 'logs' => $allLogs])
        @endforeach
    @endif
</div>

{{-- Skeleton — hanya muncul saat fetch berikutnya sedang berjalan --}}
<div class="al-timeline" id="al-skeleton" style="display:none;max-width:720px;margin-top:0;">
    @foreach(range(1,3) as $i)
    <div class="skel-item">
        <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;padding-top:.35rem;">
            <div class="skel skel-dot"></div>
        </div>
        <div class="skel-card">
            <div class="skel skel-line" style="width:{{ [40,55,35][$i-1] }}%"></div>
            <div class="skel skel-line" style="width:{{ [80,65,75][$i-1] }}%"></div>
            <div class="skel skel-line" style="width:{{ [55,45,60][$i-1] }}%;margin-bottom:0"></div>
        </div>
    </div>
    @endforeach
</div>

<div id="al-sentinel" style="height:1px;"></div>

@endsection

@push('js')
<script>
(function () {
    var nextPage  = 2;           // halaman 1 sudah dirender server
    var hasMore   = {{ $hasMore ? 'true' : 'false' }};
    var loading   = false;
    var endpoint  = '{{ route('activity-log.more', $cr) }}';
    var timeline  = document.getElementById('al-timeline');
    var skeleton  = document.getElementById('al-skeleton');
    var sentinel  = document.getElementById('al-sentinel');

    if (!hasMore) return; // semua data sudah tampil, tidak perlu observer

    function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        skeleton.style.display = 'block';

        fetch(endpoint + '?page=' + nextPage, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            skeleton.style.display = 'none';

            var tmp = document.createElement('div');
            tmp.innerHTML = data.html;

            // Append setiap item ke timeline
            Array.from(tmp.children).forEach(function (el) {
                timeline.appendChild(el);
            });

            hasMore = data.hasMore;
            nextPage++;
            loading = false;

            if (!hasMore) observer.disconnect();
        })
        .catch(function () {
            skeleton.style.display = 'none';
            loading = false;
        });
    }

    var observer = new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) loadMore();
    }, { rootMargin: '200px' });

    observer.observe(sentinel);
})();
</script>
@endpush
