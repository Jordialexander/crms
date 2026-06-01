@extends('layouts.app')

@section('content')

{{-- Page heading --}}
<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">Daftar Change Request</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">Kelola semua permintaan perubahan IT</p>
    </div>
    @can('create change_request')
    <a href="{{ route('cr.create') }}"
       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-[0.82rem] font-semibold
              bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm">
        <i class="bi bi-plus-lg text-sm leading-none"></i> Buat CR Baru
    </a>
    @endcan
</div>

{{-- Quick filter tabs (engineer only) --}}
@if(auth()->user()->roles()->where('role_type', 'engineer')->exists() && !auth()->user()->hasRole('admin'))
<div class="flex flex-wrap gap-2 mb-4">
    @php
        $tabs = [
            null          => ['label' => 'Semua',             'icon' => 'list'],
            'approved'    => ['label' => 'Perlu Dijadwalkan', 'icon' => 'calendar-plus'],
            'scheduled'   => ['label' => 'Sudah Dijadwalkan', 'icon' => 'calendar-check'],
            'in_progress' => ['label' => 'Sedang Dikerjakan', 'icon' => 'play-circle'],
            'completed'   => ['label' => 'Selesai',           'icon' => 'check-circle'],
        ];
    @endphp
    @foreach($tabs as $val => $tab)
        @php $active = request('status') === $val || ($val === null && !request('status')); @endphp
        <a href="{{ $val ? route('cr.index', ['status' => $val]) : route('cr.index') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-medium border transition-colors
                  {{ $active
                      ? 'bg-slate-800 text-white border-slate-800'
                      : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
            <i class="bi bi-{{ $tab['icon'] }} text-xs leading-none"></i>
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
@endif

{{-- Filter form --}}
<div class="bg-white border border-slate-200 rounded-xl px-4 py-3 mb-4 shadow-sm">
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <div class="flex-1 min-w-[180px]">
            <input type="text" name="search"
                   class="w-full text-[0.82rem] border border-slate-200 rounded-lg px-3 py-2
                          focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all"
                   placeholder="Cari CR number, judul..."
                   value="{{ request('search') }}">
        </div>
        <div>
            <select name="status"
                    class="text-[0.82rem] border border-slate-200 rounded-lg px-3 py-2 bg-white
                           focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all">
                <option value="">Semua Status</option>
                @foreach([
                    'draft'            => 'Draft',
                    'need_review'      => 'Need Review',
                    'under_review'     => 'Under Review',
                    'waiting_approval' => 'Waiting Approval',
                    'approved'         => 'Perlu Dijadwalkan',
                    'rejected'         => 'Rejected',
                    'scheduled'        => 'Scheduled',
                    'in_progress'      => 'In Progress',
                    'completed'        => 'Completed',
                    'failed'           => 'Failed',
                    'rollback'         => 'Rollback',
                    'canceled'         => 'Canceled',
                    'closed:completed' => 'Closed — Selesai',
                    'closed:failed'    => 'Closed — Gagal',
                    'closed:rejected'  => 'Closed — Ditolak',
                    'closed:canceled'  => 'Closed — Dibatalkan',
                ] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="risk_level"
                    class="text-[0.82rem] border border-slate-200 rounded-lg px-3 py-2 bg-white
                           focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all">
                <option value="">Semua Risiko</option>
                <option value="low"    {{ request('risk_level') == 'low'    ? 'selected' : '' }}>Low</option>
                <option value="medium" {{ request('risk_level') == 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="high"   {{ request('risk_level') == 'high'   ? 'selected' : '' }}>High</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="px-3.5 py-2 rounded-lg text-[0.82rem] font-semibold bg-blue-600 text-white
                           hover:bg-blue-700 transition-colors">
                Filter
            </button>
            <a href="{{ route('cr.index') }}"
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
                    <th>Tipe</th>
                    <th>Prioritas</th>
                    <th>Risiko</th>
                    <th>Status</th>
                    <th>Requester</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($changeRequests as $cr)
                <tr>
                    <td>
                        <a href="{{ route('cr.show', $cr) }}" class="cr-number">{{ $cr->cr_number }}</a>
                    </td>
                    <td style="max-width:200px">
                        <div class="cr-title text-ellipsis overflow-hidden whitespace-nowrap" title="{{ $cr->title }}">
                            {{ $cr->title }}
                        </div>
                    </td>
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
                    <td class="text-[0.8rem] text-slate-600">{{ $cr->requester?->name ?? '—' }}</td>
                    <td><span class="cr-date">{{ $cr->created_at->format('d M Y') }}</span></td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('cr.show', $cr) }}"
                               class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-slate-200
                                      text-slate-500 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                               title="Detail">
                                <i class="bi bi-eye text-xs leading-none"></i>
                            </a>
                            @if(in_array($cr->status, ['draft','rejected']) && (auth()->id() == $cr->requester_id || auth()->user()->hasRole('admin')))
                            <a href="{{ route('cr.edit', $cr) }}"
                               class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-slate-200
                                      text-slate-500 hover:border-slate-400 hover:text-slate-700 hover:bg-slate-50 transition-colors"
                               title="Edit">
                                <i class="bi bi-pencil text-xs leading-none"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="py-12 text-center">
                        <i class="bi bi-inbox text-4xl text-slate-300 block mb-2"></i>
                        <span class="text-slate-400 text-[0.82rem]">Belum ada change request</span>
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
