@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">Approval Rules</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">Atur jenjang approval berdasarkan prioritas, tipe, dan kategori</p>
    </div>
    <a href="{{ route('approval-rule.create') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-semibold
              bg-blue-600 text-white hover:bg-blue-700 transition-colors">
        <i class="bi bi-plus-lg text-xs leading-none"></i> Tambah Rule
    </a>
</div>

{{-- Filter bar --}}
<form method="GET"
      class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-4 py-2.5 mb-4 shadow-sm">
    <i class="bi bi-search text-slate-400 text-sm flex-shrink-0"></i>
    <input type="text" name="search"
           class="flex-1 max-w-xs h-8 px-2 rounded-lg border border-slate-200 text-[0.78rem] text-slate-700
                  bg-slate-50 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-all"
           placeholder="Cari nama rule..."
           value="{{ request('search') }}">
    <button type="submit"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[0.78rem] font-medium
                   bg-blue-600 text-white hover:bg-blue-700 transition-colors">
        <i class="bi bi-funnel text-xs"></i> Filter
    </button>
    @if(request('search'))
        <a href="{{ route('approval-rule.index') }}"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[0.78rem] font-medium
                  border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
            <i class="bi bi-x text-xs"></i> Reset
        </a>
    @endif
</form>

{{-- Table card --}}
<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-[0.78rem]">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-100">
                <th class="text-left px-4 py-2.5 text-[0.72rem] font-semibold text-slate-500 uppercase tracking-wide">Nama</th>
                <th class="text-left px-4 py-2.5 text-[0.72rem] font-semibold text-slate-500 uppercase tracking-wide">Priority</th>
                <th class="text-left px-4 py-2.5 text-[0.72rem] font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                <th class="text-left px-4 py-2.5 text-[0.72rem] font-semibold text-slate-500 uppercase tracking-wide">Category</th>
                <th class="text-left px-4 py-2.5 text-[0.72rem] font-semibold text-slate-500 uppercase tracking-wide">Max Level</th>
                <th class="text-left px-4 py-2.5 text-[0.72rem] font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                <th class="px-4 py-2.5"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rules as $r)
                @php
                    $priorityChip = match($r->priority) {
                        'low'      => 'bg-slate-100 text-slate-500',
                        'medium'   => 'bg-sky-100 text-sky-700',
                        'high'     => 'bg-amber-100 text-amber-700',
                        'critical' => 'bg-red-100 text-red-600',
                        default    => 'bg-slate-100 text-slate-500',
                    };
                @endphp
                <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition-colors last:border-b-0">
                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $r->name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold {{ $priorityChip }}">
                            {{ strtoupper($r->priority) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500">
                        {{ $r->change_type ? strtoupper($r->change_type) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-slate-500">
                        {{ $r->category ? ucfirst($r->category) : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold bg-blue-50 text-blue-600">
                            {{ $r->max_levels }} level
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($r->enabled)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[0.65rem] font-bold bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[0.65rem] font-bold bg-slate-100 text-slate-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span> Nonaktif
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('approval-rule.edit', $r) }}"
                               class="w-7 h-7 rounded-lg border border-slate-200 bg-white flex items-center justify-center
                                      text-[0.78rem] text-blue-600 hover:border-blue-400 hover:bg-blue-50 transition-colors"
                               title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('approval-rule.destroy', $r) }}" style="display:contents"
                                  onsubmit="return confirm('Hapus rule ini?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="w-7 h-7 rounded-lg border border-slate-200 bg-white flex items-center justify-center
                                               text-[0.78rem] text-red-500 hover:border-red-400 hover:bg-red-50 transition-colors"
                                        title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-400 text-[0.82rem]">
                        <i class="bi bi-inbox text-3xl block mb-2"></i>
                        Belum ada rule.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($rules->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $rules->links() }}
        </div>
    @endif
</div>

{{-- Info box --}}
<div class="flex items-start gap-3 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mt-4 text-[0.78rem] text-blue-700">
    <i class="bi bi-info-circle flex-shrink-0 mt-0.5"></i>
    <div>
        <span class="font-semibold">Cara kerja matching rule: </span>
        Sistem memilih rule paling spesifik — Priority + Type + Category → Priority + Type → Priority + Category → Priority saja.
    </div>
</div>

@endsection
