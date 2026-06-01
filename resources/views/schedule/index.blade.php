@extends('layouts.app')

@section('content')

@php
    $tabInfo = match($tab) {
        'scheduled'   => ['title' => 'Dijadwalkan',      'subtitle' => 'CR yang sudah memiliki jadwal implementasi'],
        'in_progress' => ['title' => 'Sedang Dikerjakan','subtitle' => 'CR yang sedang dalam proses implementasi'],
        'completed'   => ['title' => 'Selesai / Belum Ditutup','subtitle' => 'CR yang sudah selesai namun belum ditutup'],
        default       => ['title' => 'Perlu Dijadwalkan','subtitle' => 'CR yang sudah diapprove dan menunggu jadwal'],
    };
@endphp

{{-- Page heading --}}
<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">{{ $tabInfo['title'] }}</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">{{ $tabInfo['subtitle'] }}</p>
    </div>
</div>

{{-- Tab navigation --}}
@php
    $tabs = [
        'unscheduled' => ['label' => 'Perlu Dijadwalkan', 'icon' => 'calendar-plus'],
        'scheduled'   => ['label' => 'Dijadwalkan',       'icon' => 'calendar-check'],
        'in_progress' => ['label' => 'Sedang Dikerjakan', 'icon' => 'play-circle'],
        'completed'   => ['label' => 'Selesai / Belum Ditutup', 'icon' => 'archive'],
    ];
@endphp
<div class="flex flex-wrap gap-2 mb-4">
    @foreach($tabs as $key => $t)
        @php $isActive = $tab === $key; @endphp
        <a href="{{ route('schedule.index', ['tab' => $key]) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-medium border transition-colors
                  {{ $isActive
                      ? 'bg-slate-800 text-white border-slate-800'
                      : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
            <i class="bi bi-{{ $t['icon'] }} text-xs leading-none"></i>
            {{ $t['label'] }}
            @if(($counts[$key] ?? 0) > 0)
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[0.6rem] font-bold leading-none
                             {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">
                    {{ $counts[$key] }}
                </span>
            @endif
        </a>
    @endforeach
</div>

{{-- Table --}}
<div class="table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>CR Number</th>
                    <th>Judul CR</th>
                    @if($tab === 'unscheduled')
                        <th>Prioritas</th>
                        <th>Risiko</th>
                        <th>PIC</th>
                        <th>Diapprove</th>
                    @elseif($tab === 'scheduled')
                        <th>Rencana Mulai</th>
                        <th>Rencana Selesai</th>
                        <th>Est. Downtime</th>
                        <th>PIC</th>
                    @elseif($tab === 'in_progress')
                        <th>Rencana Mulai</th>
                        <th>Rencana Selesai</th>
                        <th>PIC</th>
                    @else
                        <th>Status</th>
                        <th>Aktual Mulai</th>
                        <th>Aktual Selesai</th>
                        <th>PIC</th>
                    @endif
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($changeRequests as $cr)
                <tr>
                    <td><a href="{{ route('cr.show', $cr) }}" class="cr-number">{{ $cr->cr_number }}</a></td>
                    <td style="max-width:200px">
                        <div class="cr-title text-ellipsis overflow-hidden whitespace-nowrap" title="{{ $cr->title }}">
                            {{ $cr->title }}
                        </div>
                    </td>

                    @if($tab === 'unscheduled')
                        <td>
                            @php
                                $priColor = match($cr->priority) {
                                    'low'      => 'bg-slate-100 text-slate-500',
                                    'medium'   => 'bg-sky-100 text-sky-700',
                                    'high'     => 'bg-amber-100 text-amber-700',
                                    'critical' => 'bg-red-100 text-red-700',
                                    default    => 'bg-slate-100 text-slate-500',
                                };
                            @endphp
                            <span class="inline-block text-[0.68rem] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-md {{ $priColor }}">
                                {{ strtoupper($cr->priority) }}
                            </span>
                        </td>
                        <td>
                            <span class="s-badge badge-{{ $cr->risk_badge ?? $cr->risk_level }}">
                                {{ strtoupper($cr->risk_level) }}
                            </span>
                        </td>
                        <td class="text-[0.8rem] text-slate-600">{{ $cr->pic->name ?? '-' }}</td>
                        <td><span class="cr-date">{{ $cr->approved_at?->format('d M Y') ?? '-' }}</span></td>

                    @elseif($tab === 'scheduled')
                        <td><span class="cr-date">{{ $cr->schedule?->planned_start?->format('d M Y H:i') ?? '-' }}</span></td>
                        <td><span class="cr-date">{{ $cr->schedule?->planned_end?->format('d M Y H:i') ?? '-' }}</span></td>
                        <td class="text-[0.8rem] text-slate-600">{{ $cr->schedule?->estimated_downtime_minutes ?? '-' }} mnt</td>
                        <td class="text-[0.8rem] text-slate-600">{{ $cr->pic->name ?? '-' }}</td>

                    @elseif($tab === 'in_progress')
                        <td><span class="cr-date">{{ $cr->schedule?->planned_start?->format('d M Y H:i') ?? '-' }}</span></td>
                        <td><span class="cr-date">{{ $cr->schedule?->planned_end?->format('d M Y H:i') ?? '-' }}</span></td>
                        <td class="text-[0.8rem] text-slate-600">{{ $cr->pic->name ?? '-' }}</td>

                    @else
                        @php
                            $badgeClass = $cr->status === 'closed' && $cr->closed_reason
                                ? 'badge-closed_' . $cr->closed_reason
                                : 'badge-' . ($cr->status ?? 'unknown');
                        @endphp
                        <td><span class="s-badge {{ $badgeClass }}">{{ $cr->status_label }}</span></td>
                        <td><span class="cr-date">{{ $cr->schedule?->actual_start?->format('d M Y H:i') ?? '-' }}</span></td>
                        <td><span class="cr-date">{{ $cr->schedule?->actual_end?->format('d M Y H:i') ?? '-' }}</span></td>
                        <td class="text-[0.8rem] text-slate-600">{{ $cr->pic->name ?? '-' }}</td>
                    @endif

                    <td>
                        @if($tab === 'in_progress')
                            <a href="{{ route('cr.show', $cr) }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[0.75rem] font-semibold
                                      bg-blue-50 border border-blue-200 text-blue-700
                                      hover:bg-blue-100 hover:border-blue-300 transition-colors">
                                <i class="bi bi-journal-plus text-xs leading-none"></i> Isi Log
                            </a>
                        @elseif($tab === 'unscheduled')
                            <a href="{{ route('cr.show', $cr) }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[0.75rem] font-semibold
                                      bg-amber-50 border border-amber-200 text-amber-700
                                      hover:bg-amber-100 hover:border-amber-300 transition-colors">
                                <i class="bi bi-calendar-plus text-xs leading-none"></i> Jadwalkan
                            </a>
                        @else
                            <a href="{{ route('cr.show', $cr) }}"
                               class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-slate-200
                                      text-slate-500 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                               title="Detail">
                                <i class="bi bi-eye text-xs leading-none"></i>
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center">
                        <i class="bi bi-calendar-x text-4xl text-slate-300 block mb-2"></i>
                        <span class="text-slate-400 text-[0.82rem]">
                            @if($tab === 'unscheduled') Tidak ada CR yang perlu dijadwalkan
                            @elseif($tab === 'scheduled') Belum ada CR yang dijadwalkan
                            @elseif($tab === 'in_progress') Tidak ada CR yang sedang dikerjakan
                            @else Belum ada CR yang selesai atau belum ditutup
                            @endif
                        </span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($changeRequests->hasPages())
    <div class="px-4 py-3 border-t border-slate-100">
        {{ $changeRequests->links() }}
    </div>
    @endif
</div>

@endsection
