@extends('layouts.app')

@section('content')

{{-- Page heading --}}
<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">Under Review</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">CR yang sedang dalam proses review dan pengisian Risk Assessment</p>
    </div>
</div>

{{-- Filter --}}
<div class="bg-white border border-slate-200 rounded-xl px-4 py-3 mb-4 shadow-sm">
    <form method="GET" class="flex flex-wrap gap-2 items-end">
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
            <a href="{{ route('under-review.index') }}"
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
                    <th class="text-center">CR Number</th>
                    <th>Judul</th>
                    <th class="text-center">Requester</th>
                    <th class="text-center">Tipe</th>
                    <th class="text-center">Prioritas</th>
                    <th class="text-center">Risiko</th>
                    <th class="text-center">Risk Assessment</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($changeRequests as $cr)
                <tr>
                    <td class="text-center"><a href="{{ route('cr.show', $cr) }}" class="cr-number">{{ $cr->cr_number }}</a></td>
                    <td style="max-width:200px">
                        <div class="cr-title text-ellipsis overflow-hidden whitespace-nowrap" title="{{ $cr->title }}">
                            {{ $cr->title }}
                        </div>
                    </td>
                    <td class="text-center text-[0.8rem] text-slate-600">{{ $cr->requester?->name ?? '—' }}</td>
                    <td class="text-center">
                        <span class="inline-block text-[0.68rem] font-semibold uppercase tracking-wide
                                     bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md">
                            {{ strtoupper($cr->change_type) }}
                        </span>
                    </td>
                    <td class="text-center">
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
                    <td class="text-center">
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
                    <td class="text-center">
                        @if($cr->riskAssessment)
                            <span class="inline-flex items-center gap-1 text-[0.68rem] font-semibold
                                         bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-md">
                                <i class="bi bi-check-circle text-xs"></i> Selesai
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-[0.68rem] font-semibold
                                         bg-amber-100 text-amber-700 px-2 py-0.5 rounded-md">
                                <i class="bi bi-exclamation-circle text-xs"></i> Belum Diisi
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('cr.show', $cr) }}"
                           class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-slate-200
                                  text-slate-500 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                           title="Detail">
                            <i class="bi bi-eye text-xs leading-none"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center">
                        <i class="bi bi-inbox text-4xl text-slate-300 block mb-2"></i>
                        <span class="text-slate-400 text-[0.82rem]">Tidak ada CR yang sedang dalam review</span>
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
