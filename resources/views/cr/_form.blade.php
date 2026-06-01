<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-4">

    {{-- Left column --}}
    <div>
        <x-form-card title="Informasi Dasar" icon="info-circle">

            <x-form-field name="title" label="Judul Change Request" required
                          value="{{ $cr->title ?? '' }}"
                          placeholder="Misal: Update konfigurasi firewall untuk akses aplikasi CRM" />

            <x-form-field name="description" label="Deskripsi Perubahan" required
                          type="textarea" :rows="4"
                          value="{{ $cr->description ?? '' }}"
                          placeholder="Jelaskan secara detail perubahan yang akan dilakukan..." />

            <x-form-field name="reason" label="Alasan Perubahan" required
                          type="textarea" :rows="3"
                          value="{{ $cr->reason ?? '' }}"
                          placeholder="Mengapa perubahan ini diperlukan?" />

            <x-form-field name="affected_service" label="Sistem/Layanan Terdampak" required
                          value="{{ $cr->affected_service ?? '' }}"
                          placeholder="Misal: Aplikasi CRM Internal, Server DB-01" />

            <x-form-field name="impact" label="Potensi Dampak"
                          type="textarea" :rows="2"
                          value="{{ $cr->impact ?? '' }}"
                          placeholder="Layanan apa yang mungkin terdampak selama implementasi?" />

            <x-form-field name="rollback_plan" label="Rollback Plan" required
                          type="textarea" :rows="3"
                          value="{{ $cr->rollback_plan ?? '' }}"
                          placeholder="Langkah-langkah jika perubahan harus dibatalkan..." />

        </x-form-card>

        {{-- Attachments --}}
        <x-form-card title="Lampiran Dokumen" icon="paperclip">

            <div class="border-2 border-dashed border-slate-200 rounded-lg px-4 py-5 text-center
                        hover:border-blue-400 hover:bg-blue-50/30 transition-colors cursor-pointer"
                 onclick="document.getElementById('file-input').click()">
                <i class="bi bi-cloud-upload text-2xl text-slate-400 block mb-1"></i>
                <p class="text-[0.8rem] text-slate-500 mb-0.5">Klik untuk memilih file</p>
                <p class="text-[0.72rem] text-slate-400">PDF, Word, Excel, PNG, JPG · Maks 10MB/file</p>
                <input id="file-input" type="file" name="attachments[]" multiple
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                       class="hidden" onchange="previewFiles(this)">
            </div>

            <div id="file-preview" class="mt-2 space-y-1.5 hidden"></div>

            @if(isset($cr) && $cr->attachments->count() > 0)
                <div class="mt-3 space-y-1.5" id="existing-attachments">
                    <p class="text-[0.72rem] font-semibold text-slate-500 uppercase tracking-wide">Lampiran saat ini:</p>
                    @foreach($cr->attachments as $att)
                        <div class="flex items-center justify-between gap-2 text-[0.8rem] text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 transition-all" id="att-{{ $att->id }}">
                            <div class="flex items-center gap-2 min-w-0">
                                <i class="bi bi-file-earmark text-slate-400 text-sm leading-none"></i>
                                <span class="truncate att-name">{{ $att->original_name }}</span>
                            </div>
                            <label class="cursor-pointer text-red-500 hover:text-red-700 flex items-center gap-1.5 shrink-0 px-2 py-0.5 rounded hover:bg-red-50 transition-colors" title="Tandai untuk dihapus saat disimpan">
                                <input type="checkbox" name="delete_attachments[]" value="{{ $att->id }}" 
                                       class="rounded border-red-300 text-red-500 focus:ring-red-500 bg-white"
                                       onchange="
                                           const box = document.getElementById('att-{{ $att->id }}');
                                           box.querySelector('.att-name').classList.toggle('line-through', this.checked);
                                           box.classList.toggle('opacity-50', this.checked);
                                           box.classList.toggle('bg-red-50/30', this.checked);
                                       ">
                                <span class="text-[0.7rem] font-semibold">Hapus</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

        </x-form-card>
    </div>

    {{-- Right column --}}
    <div>
        <x-form-card title="Klasifikasi" icon="tags">

            <div class="desc-container">
                <x-form-field name="change_type" label="Tipe Change" required type="select">
                    <option value="">Pilih Tipe Change</option>
                    @foreach($crOptions['change_types'] as $opt)
                        <option value="{{ $opt->value }}" data-desc="{{ $opt->description ?? '' }}" {{ old('change_type', $cr->change_type ?? 'normal') == $opt->value ? 'selected' : '' }}>
                            {{ $opt->label }}
                        </option>
                    @endforeach
                </x-form-field>
                <div id="desc-change_type" class="hidden -mt-3 mb-4 text-[0.72rem] text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 flex gap-2">
                    <i class="bi bi-info-circle text-blue-500 mt-0.5"></i>
                    <span class="desc-text"></span>
                </div>
            </div>

            <div class="desc-container">
                <x-form-field name="category" label="Kategori" required type="select">
                    <option value="">Pilih Kategori</option>
                    @foreach($crOptions['categories'] as $opt)
                        <option value="{{ $opt->value }}" data-desc="{{ $opt->description ?? '' }}" {{ old('category', $cr->category ?? '') == $opt->value ? 'selected' : '' }}>
                            {{ $opt->label }}
                        </option>
                    @endforeach
                </x-form-field>
                <div id="desc-category" class="hidden -mt-3 mb-4 text-[0.72rem] text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 flex gap-2">
                    <i class="bi bi-info-circle text-blue-500 mt-0.5"></i>
                    <span class="desc-text"></span>
                </div>
            </div>

            <div class="desc-container">
                <x-form-field name="priority" label="Prioritas" required type="select">
                    <option value="">Pilih Prioritas</option>
                    @foreach($crOptions['priorities'] as $opt)
                        <option value="{{ $opt->value }}" data-desc="{{ $opt->description ?? '' }}" {{ old('priority', $cr->priority ?? 'medium') == $opt->value ? 'selected' : '' }}>
                            {{ $opt->label }}
                        </option>
                    @endforeach
                </x-form-field>
                <div id="desc-priority" class="hidden -mt-3 mb-4 text-[0.72rem] text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 flex gap-2">
                    <i class="bi bi-info-circle text-blue-500 mt-0.5"></i>
                    <span class="desc-text"></span>
                </div>
            </div>

            <x-form-field name="pic_id" label="PIC Implementasi" type="select" :required="true">
                <option value="">Pilih PIC</option>
                @foreach($engineers as $eng)
                    <option value="{{ $eng->id }}" {{ old('pic_id', $cr->pic_id ?? '') == $eng->id ? 'selected' : '' }}>
                        {{ $eng->name }}
                    </option>
                @endforeach
            </x-form-field>

        </x-form-card>

        {{-- Info box --}}
        <div class="flex gap-3 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3.5 text-[0.8rem]">
            <i class="bi bi-info-circle text-blue-500 text-base shrink-0 mt-0.5"></i>
            <div class="text-slate-600 leading-relaxed">
                Setelah CR dibuat (status <strong class="text-slate-800">Draft</strong>), Anda perlu mengklik
                <strong class="text-slate-800">Submit</strong> untuk mengirimkan ke proses review dan approval.
            </div>
        </div>
    </div>

</div>

@push('js')
<script>
function previewFiles(input) {
    const preview = document.getElementById('file-preview');
    preview.innerHTML = '';
    if (!input.files.length) { preview.classList.add('hidden'); return; }
    preview.classList.remove('hidden');
    Array.from(input.files).forEach(f => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 text-[0.8rem] text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2';
        div.innerHTML = `<i class="bi bi-file-earmark text-slate-400 text-sm leading-none"></i><span class="flex-1 truncate">${f.name}</span><span class="text-slate-400 text-[0.72rem] shrink-0">${(f.size/1024/1024).toFixed(1)} MB</span>`;
        preview.appendChild(div);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const fields = ['change_type', 'category', 'priority'];
    fields.forEach(field => {
        const select = document.querySelector(`select[name="${field}"]`);
        const descBox = document.getElementById(`desc-${field}`);
        const descText = descBox ? descBox.querySelector('.desc-text') : null;

        if (select && descBox && descText) {
            const updateDesc = () => {
                const selectedOption = select.options[select.selectedIndex];
                const desc = selectedOption ? selectedOption.getAttribute('data-desc') : '';
                if (desc && desc.trim() !== '') {
                    descText.textContent = desc;
                    descBox.classList.remove('hidden');
                } else {
                    descBox.classList.add('hidden');
                }
            };
            select.addEventListener('change', updateDesc);
            updateDesc(); // Run on init
        }
    });
});
</script>
@endpush
