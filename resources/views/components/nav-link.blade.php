@props([
    'href'        => '#',
    'active'      => false,
    'icon'        => null,
    'badge'       => 0,
    'badgeColor'  => 'blue',
    'sub'         => false,
])

@php
    $badgePalette = [
        'red'    => 'bg-red-500 text-white',
        'yellow' => 'bg-amber-400 text-slate-900',
        'blue'   => 'bg-blue-500 text-white',
    ];
    $badgeClass = $badgePalette[$badgeColor] ?? $badgePalette['blue'];
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => implode(' ', array_filter([
           'flex items-center justify-between mx-2.5 my-0.5 px-3 py-2 rounded-lg text-[0.82rem] transition-colors',
           $sub  ? 'pl-8 text-[0.78rem]' : '',
           $active
               ? 'bg-blue-500/20 text-blue-300 font-medium'
               : 'text-white/60 hover:bg-white/[0.07] hover:text-white/90',
       ]))
   ]) }}>

    <span class="flex items-center gap-2.5 min-w-0">
        @if($icon)
            <i class="bi bi-{{ $icon }} w-[18px] text-[0.9rem] leading-none shrink-0
                       {{ $active ? 'text-blue-400' : '' }}"></i>
        @endif
        <span class="truncate">{{ $slot }}</span>
    </span>

    @if($badge > 0)
        <span class="shrink-0 min-w-[18px] h-[18px] px-1 rounded-full text-[0.6rem] font-bold
                     flex items-center justify-center leading-none {{ $badgeClass }}">
            {{ $badge }}
        </span>
    @endif
</a>
