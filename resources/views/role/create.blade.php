@extends('layouts.app')

@section('content')

<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">Tambah Role Baru</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">Buat role baru dengan permissions yang dapat dikustomisasi</p>
    </div>
    <a href="{{ route('role.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-medium
              border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 transition-colors">
        <i class="bi bi-arrow-left text-xs leading-none"></i> Kembali
    </a>
</div>

<form action="{{ route('role.store') }}" method="POST">
    @csrf
    @include('role._form')
    <div class="flex gap-2 mt-4">
        <button type="submit"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-[0.82rem] font-semibold
                       bg-blue-600 text-white hover:bg-blue-700 transition-colors">
            <i class="bi bi-check-circle text-xs leading-none"></i> Buat Role
        </button>
        <a href="{{ route('role.index') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-[0.82rem] font-medium
                  border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 transition-colors">
            Batal
        </a>
    </div>
</form>

@endsection
