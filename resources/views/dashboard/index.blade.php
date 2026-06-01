@extends('layouts.app')

@section('content')
@php
    $total = $stats['total'] ?? ($monitoring['total'] ?? array_sum($monitoring ?? []));
    $completed = ($monitoring['completed'] ?? 0) + ($monitoring['closed'] ?? 0);
    $pending = array_sum([($monitoring['need_review']??0),($monitoring['under_review']??0),($monitoring['waiting_approval']??0),($monitoring['unscheduled']??0),($monitoring['scheduled']??0),($monitoring['in_progress']??0),($monitoring['draft']??0)]);
    $critical = array_sum([($monitoring['rejected']??0),($monitoring['failed']??0),($monitoring['rollback']??0)]);
    $statuses = [
        ['key'=>'draft',            'label'=>'Draft',               'color'=>'#94a3b8'],
        ['key'=>'need_review',      'label'=>'Need Review',         'color'=>'#0284c7'],
        ['key'=>'under_review',     'label'=>'Under Review',        'color'=>'#ea580c'],
        ['key'=>'waiting_approval', 'label'=>'Waiting Approval',    'color'=>'#7c3aed'],
        ['key'=>'unscheduled',      'label'=>'Perlu Dijadwalkan',   'color'=>'#d97706'],
        ['key'=>'scheduled',        'label'=>'Dijadwalkan',         'color'=>'#059669'],
        ['key'=>'in_progress',      'label'=>'Dalam Proses',        'color'=>'#2563eb'],
        ['key'=>'rejected',         'label'=>'Ditolak',             'color'=>'#dc2626'],
        ['key'=>'completed',        'label'=>'Selesai',             'color'=>'#16a34a'],
        ['key'=>'failed',           'label'=>'Gagal',               'color'=>'#b91c1c'],
        ['key'=>'rollback',         'label'=>'Rollback',            'color'=>'#9333ea'],
        ['key'=>'closed',           'label'=>'Closed',              'color'=>'#475569'],
        ['key'=>'canceled',         'label'=>'Canceled',            'color'=>'#9f1239'],
    ];
    $jsStatuses = collect($statuses)->map(function($s) use($monitoring){
        $s['count'] = $monitoring[$s['key']] ?? 0; return $s;
    });
@endphp

{{-- Page heading --}}
<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">Dashboard</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">
            Selamat datang, {{ auth()->user()->name }} &nbsp;·&nbsp; {{ now()->translatedFormat('l, j F Y') }}
        </p>
    </div>
</div>

{{-- ── KPI Row ─────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-3">

    <a href="{{ route('cr.index') }}"
       class="flex items-center gap-3.5 bg-white border border-slate-200 rounded-xl px-4 py-3.5
              hover:shadow-md hover:-translate-y-0.5 hover:border-slate-300 transition-all duration-150 group">
        <div class="w-10 h-10 shrink-0 rounded-lg flex items-center justify-center bg-blue-50">
            <i class="bi bi-file-earmark-text text-blue-600 text-lg leading-none"></i>
        </div>
        <div>
            <div class="text-[1.5rem] font-bold leading-none text-slate-900">{{ $total }}</div>
            <div class="text-[0.72rem] font-medium uppercase tracking-[.04em] text-slate-400 mt-0.5">Total CR</div>
        </div>
    </a>

    <a href="{{ route('cr.index', ['status' => 'completed,closed']) }}"
       class="flex items-center gap-3.5 bg-white border border-slate-200 rounded-xl px-4 py-3.5
              hover:shadow-md hover:-translate-y-0.5 hover:border-slate-300 transition-all duration-150 group">
        <div class="w-10 h-10 shrink-0 rounded-lg flex items-center justify-center bg-green-50">
            <i class="bi bi-check-circle text-green-600 text-lg leading-none"></i>
        </div>
        <div>
            <div class="text-[1.5rem] font-bold leading-none text-slate-900">{{ $completed }}</div>
            <div class="text-[0.72rem] font-medium uppercase tracking-[.04em] text-slate-400 mt-0.5">Selesai</div>
        </div>
    </a>

    <a href="{{ route('cr.index', ['status' => 'in_progress']) }}"
       class="flex items-center gap-3.5 bg-white border border-slate-200 rounded-xl px-4 py-3.5
              hover:shadow-md hover:-translate-y-0.5 hover:border-slate-300 transition-all duration-150 group">
        <div class="w-10 h-10 shrink-0 rounded-lg flex items-center justify-center bg-amber-50">
            <i class="bi bi-hourglass-split text-amber-500 text-lg leading-none"></i>
        </div>
        <div>
            <div class="text-[1.5rem] font-bold leading-none text-slate-900">{{ $pending }}</div>
            <div class="text-[0.72rem] font-medium uppercase tracking-[.04em] text-slate-400 mt-0.5">Dalam Proses</div>
        </div>
    </a>

    <a href="{{ route('cr.index', ['status' => 'rejected,failed,rollback']) }}"
       class="flex items-center gap-3.5 bg-white border border-slate-200 rounded-xl px-4 py-3.5
              hover:shadow-md hover:-translate-y-0.5 hover:border-slate-300 transition-all duration-150 group">
        <div class="w-10 h-10 shrink-0 rounded-lg flex items-center justify-center bg-red-50">
            <i class="bi bi-exclamation-triangle text-red-600 text-lg leading-none"></i>
        </div>
        <div>
            <div class="text-[1.5rem] font-bold leading-none text-slate-900">{{ $critical }}</div>
            <div class="text-[0.72rem] font-medium uppercase tracking-[.04em] text-slate-400 mt-0.5">Perlu Perhatian</div>
        </div>
    </a>

</div>

{{-- ── Mid Row: Donut + Status List ───────────────────────────── --}}
<div class="grid grid-cols-1 xl:grid-cols-[320px_1fr] gap-3 mb-3">

    {{-- Donut chart --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[0.82rem] font-bold text-slate-900">Distribusi Status</span>
        </div>
        <div class="flex-1 flex flex-col items-center justify-center gap-3">
            <div class="relative w-[140px] h-[140px]">
                <svg viewBox="0 0 120 120" width="140" height="140">
                    <circle cx="60" cy="60" r="50" fill="none" stroke="#f1f5f9" stroke-width="16"/>
                    <g id="donut-arcs"></g>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <div class="text-[1.7rem] font-extrabold text-slate-900 leading-none">{{ $total }}</div>
                    <div class="text-[0.62rem] text-slate-400 uppercase tracking-[.05em]">Total CR</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Status list --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[0.82rem] font-bold text-slate-900">Detail Status CR</span>
            <span class="text-[0.7rem] font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-500">
                Total: {{ $total }} CR
            </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-2.5">
            @foreach($statuses as $s)
                @php $count = $monitoring[$s['key']] ?? 0; @endphp
                <a href="{{ route('cr.index', ['status' => $s['key']]) }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg border border-slate-100 bg-slate-50
                          hover:border-slate-200 hover:bg-white hover:shadow-sm hover:-translate-y-px
                          transition-all duration-150 no-underline"
                   title="{{ $s['label'] }}: {{ $count }} CR">
                    <div class="w-1 h-8 rounded-full shrink-0" style="background:{{ $s['color'] }}"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[0.62rem] font-bold uppercase tracking-[.05em] text-slate-400
                                    whitespace-nowrap overflow-hidden text-ellipsis">{{ $s['label'] }}</div>
                        <div class="text-[1.15rem] font-extrabold leading-none text-slate-900 mt-0.5">{{ $count }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

</div>

{{-- ── Bottom Row: Recent CR + Activity ───────────────────────── --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_340px] gap-3">

    {{-- Recent CR table --}}
    <div class="table-card">
        <div class="tc-header">
            <span class="text-[0.82rem] font-bold text-slate-900">Change Request Terbaru</span>
            <a href="{{ route('cr.index') }}" class="btn-xs">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>No CR</th>
                    <th>Judul</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody id="cr-table-body">
                @forelse($recentCRs->take(5) as $cr)
                    <tr onclick="window.location='{{ route('cr.show', $cr) }}'"
                        title="Buka {{ $cr->cr_number }}"
                        class="cursor-pointer">
                        <td><span class="cr-number">{{ $cr->cr_number ?? $cr->id }}</span></td>
                        <td>
                            <div class="cr-title" style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                                 title="{{ $cr->title }}">{{ $cr->title }}</div>
                        </td>
                        @php $dBadge = $cr->status === 'closed' && $cr->closed_reason
                            ? 'badge-closed_'.$cr->closed_reason
                            : 'badge-'.($cr->status ?? 'unknown'); @endphp
                        <td><span class="s-badge {{ $dBadge }}">{{ $cr->status_label ?? ucfirst(str_replace('_', ' ', $cr->status)) }}</span></td>
                        <td><span class="cr-date">{{ optional($cr->created_at)->translatedFormat('d M Y') ?? '-' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-400 py-4 text-[0.8rem]">Belum ada change request</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Activity feed --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <span class="text-[0.82rem] font-bold text-slate-900">Aktivitas Terbaru</span>
        </div>
        <div class="py-2">
            @forelse($recentCRs->take(6) as $cr)
                @php
                    $feedColor = '#2563eb';
                    $st = $cr->status ?? 'unknown';
                    if(in_array($st, ['rejected','failed'])) $feedColor = '#dc2626';
                    elseif(in_array($st, ['completed','closed'])) $feedColor = '#16a34a';
                    elseif(in_array($st, ['waiting_approval','scheduled'])) $feedColor = '#7c3aed';
                    elseif(in_array($st, ['under_review','unscheduled'])) $feedColor = '#ea580c';
                @endphp
                <a href="{{ route('cr.show', $cr) }}"
                   class="flex gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors no-underline group">
                    <div class="flex flex-col items-center pt-1">
                        <div class="w-2 h-2 rounded-full shrink-0" style="background:{{ $feedColor }}"></div>
                        @if(!$loop->last)
                            <div class="w-px flex-1 bg-slate-100 mt-1"></div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0 pb-1">
                        <div class="text-[0.76rem] text-slate-600 leading-snug">
                            <strong class="text-slate-900">{{ $cr->cr_number ?? $cr->id }}</strong>
                            — {{ \Illuminate\Support\Str::limit($cr->title, 80) }}
                        </div>
                        <div class="text-[0.67rem] text-slate-400 mt-0.5">
                            {{ optional($cr->created_at)->diffForHumans() ?? '-' }}
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-4 py-6 text-center text-slate-400 text-[0.8rem]">Belum ada aktivitas</div>
            @endforelse
        </div>
    </div>

</div>

@push('js')
<script>
const STATUS_DATA = {!! $jsStatuses->toJson() !!};
const TOTAL = {{ $total }};

document.addEventListener('DOMContentLoaded', function () {
    const list = STATUS_DATA.filter(s => s.count > 0).sort((a, b) => b.count - a.count).slice(0, 6);
    const topTotal = list.reduce((a, s) => a + s.count, 0);
    const rest = TOTAL - topTotal;
    const arcList = rest > 0 ? [...list, {label: 'Lainnya', color: '#e2e8f0', count: rest}] : list;

    const R = 50, cx = 60, cy = 60;
    const circumference = 2 * Math.PI * R;
    let offset = 0;
    const gap = 2;

    const arcs = arcList.map(s => {
        const deg = (s.count / (TOTAL || 1)) * 360 - gap;
        const dashLen = (deg / 360) * circumference;
        const gapLen = circumference - dashLen;
        const rotate = offset;
        offset += (s.count / (TOTAL || 1)) * 360;
        return `<circle cx="${cx}" cy="${cy}" r="${R}" fill="none" stroke="${s.color}" stroke-width="16"
            stroke-dasharray="${dashLen} ${gapLen}" stroke-dashoffset="${circumference * 0.25}"
            transform="rotate(${rotate - 90} ${cx} ${cy})" style="transition:stroke-dasharray .6s ease"/>`;
    }).join('');

    document.getElementById('donut-arcs').innerHTML = arcs;
});
</script>
@endpush

@endsection
