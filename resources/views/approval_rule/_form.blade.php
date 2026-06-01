{{-- Shared form partial for approval rule create & edit --}}
<x-form-card title="Konfigurasi Rule" icon="sliders">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">

        <div class="md:col-span-2">
            <x-form-field name="name" label="Nama Rule" :required="true"
                          :value="old('name', $rule->name ?? '')"
                          placeholder="Contoh: High — 2 level approval" />
        </div>

        <x-form-field name="priority" label="Prioritas" type="select" :required="true">
            @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $label)
                <option value="{{ $val }}" {{ old('priority', $rule->priority ?? '') === $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </x-form-field>

        <x-form-field name="max_levels" label="Max Level Approval" type="number" :required="true"
                      :value="old('max_levels', $rule->max_levels ?? 1)"
                      hint="Contoh: 2 berarti butuh atasan level 1 dan level 2." />

        <x-form-field name="change_type" label="Tipe Change (opsional)" type="select">
            <option value="">Semua tipe</option>
            @foreach(['standard' => 'Standard', 'normal' => 'Normal', 'emergency' => 'Emergency'] as $val => $label)
                <option value="{{ $val }}" {{ old('change_type', $rule->change_type ?? '') === $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </x-form-field>

        <x-form-field name="category" label="Kategori (opsional)" type="select"
                      hint="Kosongkan untuk semua kategori.">
            <option value="">Semua kategori</option>
            @foreach(['infrastructure' => 'Infrastructure', 'application' => 'Application', 'database' => 'Database', 'network' => 'Network', 'security' => 'Security', 'other' => 'Other'] as $val => $label)
                <option value="{{ $val }}" {{ old('category', $rule->category ?? '') === $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </x-form-field>

    </div>

    <div class="pt-3 border-t border-slate-100 mt-1">
        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" name="enabled" value="1"
                   class="rounded border-slate-300 accent-blue-600"
                   {{ old('enabled', $rule->enabled ?? true) ? 'checked' : '' }}>
            <span class="text-[0.82rem] font-medium text-slate-700">Rule Aktif</span>
        </label>
    </div>
</x-form-card>
