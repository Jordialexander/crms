@extends('layouts.app')

@section('content')

{{-- Page heading --}}
<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">Buat Change Request Baru</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">Isi formulir berikut untuk mengajukan perubahan IT</p>
    </div>
    <a href="{{ route('cr.index') }}"
       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-[0.82rem] font-medium
              border border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition-colors">
        <i class="bi bi-arrow-left text-sm leading-none"></i> Kembali
    </a>
</div>

<form method="POST" action="{{ route('cr.store') }}" enctype="multipart/form-data">
    @csrf
    @include('cr._form', ['engineers' => $engineers])

    <div class="flex items-center gap-2 mt-2">
        <button type="submit"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-[0.85rem] font-semibold
                       bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm">
            <i class="bi bi-save text-sm leading-none"></i> Simpan Draft
        </button>
        <a href="{{ route('cr.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg text-[0.85rem] font-medium
                  border border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition-colors">
            Batal
        </a>
    </div>
</form>

@endsection
