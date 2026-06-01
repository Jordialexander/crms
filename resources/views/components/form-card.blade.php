@props(['title', 'icon' => null])

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
    <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100">
        @if($icon)
            <i class="bi bi-{{ $icon }} text-blue-500 text-sm leading-none"></i>
        @endif
        <span class="text-[0.82rem] font-bold text-slate-800">{{ $title }}</span>
    </div>
    <div class="px-4 py-4">
        {{ $slot }}
    </div>
</div>
