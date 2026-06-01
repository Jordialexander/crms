@props([
    'name',
    'label',
    'required' => false,
    'type'     => 'text',
    'rows'     => 3,
    'value'    => '',
    'placeholder' => '',
    'hint'     => null,
])

@php
    $hasError = $errors->has($name);
    $baseInput = 'w-full text-[0.82rem] text-slate-800 border rounded-lg px-3 py-2.5
                  focus:outline-none focus:ring-2 transition-all placeholder:text-slate-400 bg-white';
    $inputClass = $baseInput . ($hasError
        ? ' border-red-400 focus:border-red-500 focus:ring-red-500/10'
        : ' border-slate-200 focus:border-blue-500 focus:ring-blue-500/10');
@endphp

<div class="mb-4 last:mb-0">
    <label class="block text-[0.78rem] font-semibold text-slate-600 mb-1.5">
        {{ $label }}
        @if($required) <span class="text-red-500 ml-0.5">*</span> @endif
    </label>

    @if($type === 'textarea')
        <textarea name="{{ $name }}" rows="{{ $rows }}"
                  placeholder="{{ $placeholder }}"
                  class="{{ $inputClass }}">{{ old($name, $value) }}</textarea>
    @elseif($type === 'select')
        <select name="{{ $name }}" class="{{ $inputClass }}">
            {{ $slot }}
        </select>
    @else
        <input type="{{ $type }}" name="{{ $name }}"
               value="{{ old($name, $value) }}"
               placeholder="{{ $placeholder }}"
               class="{{ $inputClass }}">
    @endif

    @error($name)
        <p class="mt-1 text-[0.72rem] text-red-500">{{ $message }}</p>
    @enderror

    @if($hint)
        <p class="mt-1 text-[0.72rem] text-slate-400">{{ $hint }}</p>
    @endif
</div>
