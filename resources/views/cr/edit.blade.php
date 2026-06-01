@extends('layouts.app')

@section('content')

<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">Edit Change Request</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">{{ $cr->cr_number }} — {{ $cr->title }}</p>
    </div>
    <a href="{{ route('cr.show', $cr) }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-medium
              border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 transition-colors">
        <i class="bi bi-arrow-left text-xs leading-none"></i> Kembali
    </a>
</div>

@if($errors->any())
<div class="flex gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-4 text-[0.8rem] text-red-700">
    <i class="bi bi-exclamation-triangle shrink-0 mt-0.5"></i>
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('cr.update', $cr) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('cr._form', ['engineers' => $engineers])
    <div class="flex gap-2 mt-4">
        <button type="submit"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-[0.82rem] font-semibold
                       bg-blue-600 text-white hover:bg-blue-700 transition-colors">
            <i class="bi bi-save text-xs leading-none"></i> Simpan Perubahan
        </button>
        <a href="{{ route('cr.show', $cr) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-[0.82rem] font-medium
                  border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 transition-colors">
            Batal
        </a>
    </div>
</form>

@endsection
