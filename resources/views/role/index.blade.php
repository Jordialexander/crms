@extends('layouts.app')

@push('css')
<style>
.rl-children.collapsed        { display: none; }
.rl-toggle.open > i           { transform: rotate(90deg); }
.rl-toggle > i                { transition: transform 150ms ease; }
</style>
@endpush

@section('content')

{{-- Page Header --}}
<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">Manajemen Roles</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">Kelola roles, permissions, dan hierarki secara fleksibel</p>
    </div>
    <a href="{{ route('role.create') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-semibold
              bg-blue-600 text-white hover:bg-blue-700 transition-colors">
        <i class="bi bi-plus-lg text-xs leading-none"></i> Tambah Role
    </a>
</div>

{{-- Filter bar --}}
<form method="GET"
      class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-4 py-2.5 mb-4 shadow-sm">
    <i class="bi bi-search text-slate-400 text-sm flex-shrink-0"></i>
    <input type="text" name="search"
           class="flex-1 max-w-xs h-8 px-2 rounded-lg border border-slate-200 text-[0.78rem] text-slate-700
                  bg-slate-50 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-all"
           placeholder="Cari nama atau deskripsi role..."
           value="{{ $search ?? '' }}">
    <button type="submit"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[0.78rem] font-medium
                   bg-blue-600 text-white hover:bg-blue-700 transition-colors">
        <i class="bi bi-funnel text-xs"></i> Cari
    </button>
    @if($search)
        <a href="{{ route('role.index') }}"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[0.78rem] font-medium
                  border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
            <i class="bi bi-x text-xs"></i> Reset
        </a>
    @endif
    <div class="ml-auto text-[0.72rem] text-slate-400">
        <i class="bi bi-diagram-3 mr-1"></i> Tampilan Hierarki
    </div>
</form>

{{-- Tree card --}}
<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
        <span class="text-[0.82rem] font-bold text-slate-800">Struktur Role</span>
        <span class="text-[0.72rem] text-slate-400">
            @if($search)
                {{ $flatRoles->count() }} role ditemukan untuk "{{ $search }}"
            @else
                {{ $roots->count() }} root role
            @endif
        </span>
    </div>

    @if($search)
        @if($flatRoles->isEmpty())
            <div class="py-12 text-center text-slate-400 text-[0.82rem]">
                <i class="bi bi-search text-3xl block mb-2"></i>
                Tidak ada role yang cocok dengan "{{ $search }}"
            </div>
        @else
            <div class="py-1">
                @foreach($flatRoles as $role)
                    @include('role._tree_node', ['role' => $role, 'depth' => 0])
                @endforeach
            </div>
        @endif
    @else
        @if($roots->isEmpty())
            <div class="py-12 text-center text-slate-400 text-[0.82rem]">
                <i class="bi bi-diagram-3 text-3xl block mb-2"></i>
                Belum ada role. <a href="{{ route('role.create') }}" class="text-blue-600 hover:underline">Tambah sekarang</a>
            </div>
        @else
            <div class="py-1" id="rl-root-tree">
                @foreach($roots as $root)
                    @include('role._tree_node', ['role' => $root, 'depth' => 0])
                @endforeach
            </div>
        @endif
    @endif
</div>

@endsection

@push('js')
<script>
(function () {
    document.querySelectorAll('.rl-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var wrap = btn.closest('.rl-node-wrap');
            if (!wrap) return;
            var children = wrap.querySelector(':scope > .rl-children');
            if (!children) return;
            var isOpen = !children.classList.contains('collapsed');
            children.classList.toggle('collapsed', isOpen);
            btn.classList.toggle('open', !isOpen);
        });
    });
})();
</script>
@endpush
