@extends('layouts.app')

@section('content')

{{-- Page header --}}
<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">{{ $typeLabel }}</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">Kelola pilihan {{ $typeLabel }} secara dinamis</p>
    </div>

    {{-- Type switcher tabs --}}
    <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1">
        @foreach($types as $t => $label)
        <a href="{{ route('cr-options.index', $t) }}"
           class="px-3 py-1.5 rounded-md text-[0.75rem] font-medium transition-colors
                  {{ $type === $t
                      ? 'bg-white text-slate-800 shadow-sm'
                      : 'text-slate-500 hover:text-slate-700' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-4">

    {{-- Options list --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">

        <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 bg-slate-50">
            <i class="bi bi-tags text-slate-400 text-sm leading-none"></i>
            <h2 class="text-[0.82rem] font-bold text-slate-700">Daftar {{ $typeLabel }}</h2>
            <span class="ml-auto text-[0.68rem] font-semibold bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">
                {{ $options->where('is_active', true)->count() }}/{{ $options->count() }} aktif
            </span>
        </div>

        <div class="divide-y divide-slate-50">
            @forelse($options as $opt)
            <div class="group">
                <div class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50/60 transition-colors
                            {{ !$opt->is_active ? 'opacity-50' : '' }}">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[0.82rem] font-semibold text-slate-800">{{ $opt->label }}</span>
                            <span class="text-[0.68rem] text-slate-400 font-mono bg-slate-100 px-1.5 py-0.5 rounded">{{ $opt->value }}</span>
                            @if(!$opt->is_active)
                            <span class="text-[0.62rem] font-bold bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full">nonaktif</span>
                            @endif
                        </div>
                        @if($opt->description)
                        <p class="text-[0.72rem] text-slate-400 mt-0.5 leading-relaxed">{{ $opt->description }}</p>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button type="button" onclick="toggleEdit({{ $opt->id }})"
                                class="w-7 h-7 rounded-md border border-slate-200 bg-white flex items-center justify-center
                                       text-[0.75rem] text-blue-600 hover:border-blue-400 hover:bg-blue-50 transition-colors"
                                title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>

                        <form method="POST" action="{{ route('cr-options.toggle', [$type, $opt]) }}" style="display:contents">
                            @csrf
                            <button type="submit"
                                    class="w-7 h-7 rounded-md border border-slate-200 bg-white flex items-center justify-center
                                           text-[0.75rem] transition-colors
                                           {{ $opt->is_active
                                               ? 'text-amber-500 hover:border-amber-400 hover:bg-amber-50'
                                               : 'text-green-600 hover:border-green-400 hover:bg-green-50' }}"
                                    title="{{ $opt->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                <i class="bi bi-{{ $opt->is_active ? 'eye-slash' : 'eye' }}"></i>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Inline edit form --}}
                <div id="edit-row-{{ $opt->id }}" class="hidden px-4 pb-3 pt-2 bg-blue-50/40 border-t border-blue-100">
                    <form method="POST" action="{{ route('cr-options.update', [$type, $opt]) }}">
                        @csrf @method('PUT')
                        <div class="mb-2">
                            <label class="text-[0.67rem] font-semibold text-slate-500 uppercase tracking-wide block mb-0.5">Label</label>
                            <input type="text" name="label" value="{{ $opt->label }}" required
                                   class="w-full h-8 px-2.5 rounded-lg border border-slate-200 text-[0.78rem] text-slate-700
                                          focus:outline-none focus:border-blue-500 bg-white transition-all">
                        </div>
                        <div class="mb-2">
                            <label class="text-[0.67rem] font-semibold text-slate-500 uppercase tracking-wide block mb-0.5">Deskripsi</label>
                            <input type="text" name="description" value="{{ $opt->description }}"
                                   placeholder="Penjelasan singkat tentang option ini..."
                                   class="w-full h-8 px-2.5 rounded-lg border border-slate-200 text-[0.78rem] text-slate-700
                                          placeholder:text-slate-400 focus:outline-none focus:border-blue-500 bg-white transition-all">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-1.5 text-[0.78rem] text-slate-600 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1"
                                       class="rounded border-slate-300 accent-blue-600"
                                       {{ $opt->is_active ? 'checked' : '' }}>
                                Aktif
                            </label>
                            <div class="ml-auto flex gap-1.5">
                                <button type="button" onclick="toggleEdit({{ $opt->id }})"
                                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-[0.72rem] text-slate-600
                                               hover:bg-slate-50 transition-colors">Batal</button>
                                <button type="submit"
                                        class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-[0.72rem] font-semibold
                                               hover:bg-blue-700 transition-colors">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @empty
            <div class="px-4 py-10 text-center text-slate-400 text-[0.8rem]">
                <i class="bi bi-inbox block text-3xl mb-1.5"></i>
                Belum ada option untuk {{ $typeLabel }}.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Add new form --}}
    <div>
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 bg-slate-50">
                <i class="bi bi-plus-circle text-blue-500 text-sm leading-none"></i>
                <h2 class="text-[0.82rem] font-bold text-slate-700">Tambah {{ $typeLabel }}</h2>
            </div>
            <div class="px-4 py-4">
                <form method="POST" action="{{ route('cr-options.store', $type) }}">
                    @csrf
                    <div class="space-y-3">
                        <div>
                            <label class="text-[0.72rem] font-semibold text-slate-600 block mb-1">
                                Label <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="label" value="{{ old('label') }}"
                                   placeholder="Tampilan di form (misal: Emergency)"
                                   class="w-full h-9 px-3 rounded-lg border border-slate-200 text-[0.8rem] text-slate-700
                                          placeholder:text-slate-400 focus:outline-none focus:border-blue-500 bg-white transition-all"
                                   required>
                        </div>
                        <div>
                            <label class="text-[0.72rem] font-semibold text-slate-600 block mb-1">
                                Value <span class="text-red-500">*</span>
                                <span class="font-normal text-slate-400">(kode unik, tidak bisa diubah)</span>
                            </label>
                            <input type="text" name="value" value="{{ old('value') }}"
                                   placeholder="misal: emergency"
                                   class="w-full h-9 px-3 rounded-lg border border-slate-200 text-[0.8rem] text-slate-700 font-mono
                                          placeholder:text-slate-400 focus:outline-none focus:border-blue-500 bg-white transition-all"
                                   required>
                            @error('value')
                            <p class="text-[0.72rem] text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-[0.72rem] font-semibold text-slate-600 block mb-1">Deskripsi</label>
                            <input type="text" name="description" value="{{ old('description') }}"
                                   placeholder="Penjelasan singkat (opsional)"
                                   class="w-full h-9 px-3 rounded-lg border border-slate-200 text-[0.8rem] text-slate-700
                                          placeholder:text-slate-400 focus:outline-none focus:border-blue-500 bg-white transition-all">
                        </div>
                        <button type="submit"
                                class="w-full h-9 rounded-lg bg-blue-600 text-white text-[0.8rem] font-semibold
                                       hover:bg-blue-700 transition-colors flex items-center justify-center gap-1.5">
                            <i class="bi bi-plus-lg text-xs leading-none"></i> Tambah
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 mt-4 text-[0.75rem] text-amber-700">
            <i class="bi bi-exclamation-triangle shrink-0 mt-0.5"></i>
            <div>
                <span class="font-semibold">Value permanen</span> — tidak bisa diubah setelah disimpan. Option yang dinonaktifkan tidak muncul di form CR baru, namun CR yang sudah ada tetap terbaca.
            </div>
        </div>
    </div>

</div>

@endsection

@push('js')
<script>
function toggleEdit(id) {
    document.getElementById('edit-row-' + id).classList.toggle('hidden');
}
</script>
@endpush
