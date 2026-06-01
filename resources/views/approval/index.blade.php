@extends('layouts.app')

@section('content')

@php
    $currentStatus = request('status', '');
    $pageInfo = match($currentStatus) {
        'under_review'     => ['title' => 'Under Review',     'subtitle' => 'CR yang sedang dalam tahap review dan pengisian risk assessment'],
        'waiting_approval' => ['title' => 'Waiting Approval', 'subtitle' => 'CR yang menunggu keputusan approval, atau tindakan pasca failed/rollback'],
        default            => ['title' => 'Waiting Approval', 'subtitle' => 'CR yang menunggu tindakan dari anda'],
    };
@endphp

{{-- Page heading --}}
<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">{{ $pageInfo['title'] }}</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">{{ $pageInfo['subtitle'] }}</p>
    </div>
</div>

{{-- Status tabs --}}
<div class="flex flex-wrap gap-2 mb-4">
    @php
        $statusTabs = [
            ''                 => ['label' => 'Semua',            'icon' => 'list'],
            'under_review'     => ['label' => 'Under Review',     'icon' => 'pencil-square'],
            'waiting_approval' => ['label' => 'Waiting Approval', 'icon' => 'hourglass'],
        ];
    @endphp
    @foreach($statusTabs as $val => $tab)
        @php $isActive = $currentStatus === $val; @endphp
        <a href="{{ route('approval.index', $val ? ['status' => $val] : []) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-medium border transition-colors
                  {{ $isActive
                      ? 'bg-slate-800 text-white border-slate-800'
                      : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
            <i class="bi bi-{{ $tab['icon'] }} text-xs leading-none"></i>
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>

{{-- Filter --}}
<div class="bg-white border border-slate-200 rounded-xl px-4 py-3 mb-4 shadow-sm">
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <input type="hidden" name="status" value="{{ $currentStatus }}">
        <div class="flex-1 min-w-[220px]">
            <input type="text" name="search"
                   class="w-full text-[0.82rem] border border-slate-200 rounded-lg px-3 py-2
                          focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all"
                   placeholder="Cari CR number atau judul..."
                   value="{{ request('search') }}">
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="px-3.5 py-2 rounded-lg text-[0.82rem] font-semibold bg-blue-600 text-white
                           hover:bg-blue-700 transition-colors">
                Filter
            </button>
            <a href="{{ route('approval.index', $currentStatus ? ['status' => $currentStatus] : []) }}"
               class="px-3.5 py-2 rounded-lg text-[0.82rem] font-medium border border-slate-200
                      text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition-colors">
                Reset
            </a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>CR Number</th>
                    <th>Judul</th>
                    <th>Requester</th>
                    <th>Tipe</th>
                    <th>Prioritas</th>
                    <th>Risiko</th>
                    <th>Status</th>
                    <th>Disubmit</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($changeRequests as $cr)
                <tr>
                    <td><a href="{{ route('approval.show', $cr) }}" class="cr-number">{{ $cr->cr_number }}</a></td>
                    <td style="max-width:200px">
                        <div class="cr-title text-ellipsis overflow-hidden whitespace-nowrap" title="{{ $cr->title }}">
                            {{ $cr->title }}
                        </div>
                    </td>
                    <td class="text-[0.8rem] text-slate-600">{{ $cr->requester->name }}</td>
                    <td>
                        <span class="inline-block text-[0.68rem] font-semibold uppercase tracking-wide
                                     bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md">
                            {{ strtoupper($cr->change_type) }}
                        </span>
                    </td>
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
                        <span class="inline-block text-[0.68rem] font-semibold uppercase tracking-wide
                                     px-2 py-0.5 rounded-md {{ $priColor }}">
                            {{ strtoupper($cr->priority) }}
                        </span>
                    </td>
                    <td>
                        @if($cr->riskAssessment)
                            @php
                                $riskColor = match($cr->riskAssessment->risk_level) {
                                    'low'    => 'bg-emerald-100 text-emerald-700',
                                    'medium' => 'bg-amber-100 text-amber-700',
                                    'high'   => 'bg-red-100 text-red-700',
                                    default  => 'bg-slate-100 text-slate-500',
                                };
                            @endphp
                            <span class="inline-block text-[0.68rem] font-semibold uppercase tracking-wide
                                         px-2 py-0.5 rounded-md {{ $riskColor }}">
                                {{ strtoupper($cr->riskAssessment->risk_level) }}
                            </span>
                        @else
                            <span class="text-slate-300 text-sm">—</span>
                        @endif
                    </td>
                    @php
                        $badgeClass = $cr->status === 'closed' && $cr->closed_reason
                            ? 'badge-closed_' . $cr->closed_reason
                            : 'badge-' . ($cr->status ?? 'unknown');
                    @endphp
                    <td><span class="s-badge {{ $badgeClass }}">{{ $cr->status_label }}</span></td>
                    <td><span class="cr-date">{{ $cr->submitted_at?->format('d M Y') ?? '-' }}</span></td>
                    <td class="text-center">
                        @if(in_array($cr->status, ['failed', 'rollback']))
                            <a href="{{ route('approval.show', $cr) }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[0.75rem] font-semibold
                                      bg-amber-50 border border-amber-200 text-amber-700
                                      hover:bg-amber-100 hover:border-amber-300 transition-colors">
                                <i class="bi bi-exclamation-triangle text-xs leading-none"></i> Keputusan
                            </a>
                        @else
                            <a href="{{ route('approval.show', $cr) }}"
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
                    <td colspan="9" class="py-12 text-center">
                        <i class="bi bi-inbox text-4xl text-slate-300 block mb-2"></i>
                        <span class="text-slate-400 text-[0.82rem]">Tidak ada CR untuk direview</span>
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
