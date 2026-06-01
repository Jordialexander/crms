{{-- Shared form partial for role create & edit --}}
<x-form-card title="Informasi Role" icon="shield-check">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
        <x-form-field name="name" label="Nama Role" :required="true"
                      :value="old('name', $role->name ?? '')"
                      placeholder="Contoh: senior_engineer" />

        <x-form-field name="role_type" label="Role Type" type="select">
            <option value="">Tidak diset</option>
            <option value="engineer"  {{ old('role_type',  $role->role_type  ?? '') === 'engineer'  ? 'selected' : '' }}>Engineer</option>
            <option value="approver"  {{ old('role_type',  $role->role_type  ?? '') === 'approver'  ? 'selected' : '' }}>Approver</option>
            <option value="requester" {{ old('role_type',  $role->role_type  ?? '') === 'requester' ? 'selected' : '' }}>Requester</option>
        </x-form-field>

        <x-form-field name="parent_id" label="Parent Role" type="select"
                      hint="Pilih parent role untuk membuat hierarki. Level diatur otomatis.">
            <option value="">Tidak ada (Root Role)</option>
            @foreach($parentRoles as $pr)
                <option value="{{ $pr->id }}"
                    {{ old('parent_id', $role->parent_id ?? '') == $pr->id ? 'selected' : '' }}>
                    {{ str_repeat('— ', $pr->level - 1) }} {{ $pr->name }}
                </option>
            @endforeach
        </x-form-field>

        <div class="md:col-span-2">
            <x-form-field name="description" label="Deskripsi" type="textarea" :rows="2"
                          :value="old('description', $role->description ?? '')"
                          placeholder="Jelaskan peran dan tanggung jawab role ini..." />
        </div>
    </div>
</x-form-card>

{{-- Permissions --}}
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
    <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100">
        <i class="bi bi-key text-blue-500 text-sm leading-none"></i>
        <span class="text-[0.82rem] font-bold text-slate-800">Permissions (Hardcoded)</span>
    </div>
    <div class="px-4 py-4">
        @if($errors->has('permissions'))
            <div class="mb-3 flex items-center gap-2 bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-[0.78rem] text-red-600">
                <i class="bi bi-exclamation-triangle flex-shrink-0"></i> {{ $errors->first('permissions') }}
            </div>
        @endif

        @forelse($permissions as $category => $perms)
            <div class="mb-4 last:mb-0">
                <div class="text-[0.72rem] font-bold text-blue-600 uppercase tracking-wide mb-2">{{ $category }}</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-1.5">
                    @foreach($perms as $permKey => $permLabel)
                        <label class="flex items-start gap-2 cursor-pointer select-none
                                      bg-slate-50 hover:bg-blue-50 rounded-lg px-3 py-2 border border-slate-100
                                      hover:border-blue-200 transition-colors">
                            <input type="checkbox" name="permissions[]" value="{{ $permKey }}"
                                   id="perm_{{ md5($permKey) }}"
                                   class="mt-0.5 flex-shrink-0 accent-blue-600"
                                   {{ in_array($permKey, old('permissions', $rolePermissions ?? [])) ? 'checked' : '' }}>
                            <div>
                                <div class="text-[0.75rem] font-semibold text-slate-700">{{ $permKey }}</div>
                                <div class="text-[0.68rem] text-slate-400">{{ $permLabel }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-[0.82rem] text-amber-700">
                <i class="bi bi-exclamation-triangle flex-shrink-0"></i>
                Daftar permission belum tersedia di config.
            </div>
        @endforelse
    </div>
</div>
