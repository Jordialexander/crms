@php
    $selectedRoleName = old('role', $user?->getRoleNames()->first() ?? '');
    $selectedRole     = collect($roles)->firstWhere('name', $selectedRoleName);
    $parentRoleName   = $selectedRole?->parent?->name;
@endphp

<x-form-card title="Informasi Akun" icon="person">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">

        <x-form-field name="name" label="Nama Lengkap" :required="true"
                      :value="old('name', $user->name ?? '')" />

        <x-form-field name="username" label="Username" :required="true"
                      :value="old('username', $user->username ?? '')" />

        <x-form-field name="email" label="Email" type="email"
                      :value="old('email', $user->email ?? '')" />

        <x-form-field name="role" label="Role" type="select" :required="true">
            @foreach($roles as $role)
            <option value="{{ $role->name }}"
                {{ old('role', $user?->getRoleNames()->first() ?? '') == $role->name ? 'selected' : '' }}>
                {{ strtoupper($role->name) }}
            </option>
            @endforeach
        </x-form-field>

        <div class="mb-4 last:mb-0">
            <label class="block text-[0.78rem] font-semibold text-slate-600 mb-1.5">Atasan dari Role</label>
            <input type="text" readonly
                   value="{{ $parentRoleName ? strtoupper($parentRoleName) : '— Root / Tidak punya atasan —' }}"
                   class="w-full text-[0.82rem] text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 cursor-default">
            <p class="mt-1 text-[0.72rem] text-slate-400">Diambil otomatis dari hierarchy role.</p>
        </div>

        <x-form-field name="password"
                      label="Password{{ isset($user) ? ' (kosongkan jika tidak diubah)' : '' }}"
                      type="password"
                      :required="!isset($user)" />

        <x-form-field name="password_confirmation" label="Konfirmasi Password" type="password" />

    </div>

    {{-- Checkboxes --}}
    <div class="flex flex-col gap-2 pt-2 border-t border-slate-100 mt-2">
        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" name="is_active" value="1" id="is_active"
                   class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                   {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
            <span class="text-[0.82rem] text-slate-700">Akun Aktif</span>
        </label>
        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" name="notify_email" value="1" id="notify_email"
                   class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                   {{ old('notify_email', $user->notify_email ?? true) ? 'checked' : '' }}>
            <span class="text-[0.82rem] text-slate-700">Kirim notifikasi via email</span>
        </label>
    </div>
</x-form-card>
