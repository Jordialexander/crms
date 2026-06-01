@php
    $hasChildren = $role->children->isNotEmpty();

    $typeChip = match($role->role_type) {
        'engineer'  => 'bg-blue-50 text-blue-600',
        'approver'  => 'bg-violet-50 text-violet-600',
        'requester' => 'bg-green-50 text-green-700',
        default     => null,
    };
    $typeLabel = match($role->role_type) {
        'engineer'  => 'Engineer',
        'approver'  => 'Approver',
        'requester' => 'Requester',
        default     => null,
    };

    $iconBg    = $depth === 0 ? 'bg-blue-50'   : 'bg-slate-50';
    $iconColor = $depth === 0 ? 'text-blue-600' : 'text-slate-400';
    $iconClass = $hasChildren  ? 'bi-diagram-3'  : 'bi-shield-check';
@endphp

<div class="rl-node-wrap" data-depth="{{ $depth }}">

    {{-- ── Row ───────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-2 px-4 py-2.5 border-b border-slate-50
                hover:bg-slate-50/70 transition-colors">

        {{-- Depth indent spacers (each level = 1.25rem) --}}
        @for($i = 0; $i < $depth; $i++)
            <div class="w-5 flex-shrink-0 relative self-stretch">
                {{-- Vertical guide line --}}
                <div class="absolute left-1/2 top-0 bottom-0 w-px bg-slate-150"
                     style="background:#e9ecf0"></div>
                {{-- Horizontal tick only on last spacer --}}
                @if($i === $depth - 1)
                    <div class="absolute left-1/2 top-1/2 w-2.5 h-px"
                         style="background:#e2e8f0"></div>
                @endif
            </div>
        @endfor

        {{-- Toggle (expand/collapse) or leaf spacer --}}
        @if($hasChildren)
            <button type="button"
                    class="rl-toggle w-5 h-5 flex-shrink-0 rounded border border-slate-200 bg-slate-50
                           flex items-center justify-center
                           hover:border-blue-400 hover:bg-blue-50 transition-colors">
                <i class="bi bi-chevron-right text-[0.6rem] text-slate-500
                           transition-transform duration-150"></i>
            </button>
        @else
            <div class="w-5 flex-shrink-0"></div>
        @endif

        {{-- Role icon --}}
        <div class="w-7 h-7 rounded-lg flex-shrink-0 flex items-center justify-center {{ $iconBg }}">
            <i class="bi {{ $iconClass }} text-[0.8rem] {{ $iconColor }}"></i>
        </div>

        {{-- Name + description --}}
        <div class="flex-1 min-w-0">
            <div class="text-[0.82rem] font-semibold text-slate-800 truncate">{{ $role->name }}</div>
            @if($role->description)
                <div class="text-[0.7rem] text-slate-400 truncate leading-snug">{{ $role->description }}</div>
            @endif
        </div>

        {{-- Meta chips --}}
        <div class="flex items-center gap-1 flex-shrink-0">
            @if($typeLabel)
                <span class="inline-flex px-1.5 py-0.5 rounded-full text-[0.62rem] font-bold {{ $typeChip }}">
                    {{ $typeLabel }}
                </span>
            @endif
            <span class="inline-flex px-1.5 py-0.5 rounded-full text-[0.62rem] font-bold
                         bg-slate-100 text-slate-400">
                L{{ $role->level }}
            </span>
            @if(count($role->abilities ?? []) > 0)
                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full
                             text-[0.62rem] font-bold bg-sky-50 text-sky-600">
                    <i class="bi bi-key" style="font-size:.55rem"></i>
                    {{ count($role->abilities) }}
                </span>
            @endif
            @if(($role->users_count ?? 0) > 0)
                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full
                             text-[0.62rem] font-bold bg-green-50 text-green-700">
                    <i class="bi bi-people" style="font-size:.55rem"></i>
                    {{ $role->users_count }}
                </span>
            @endif
            @if($hasChildren)
                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full
                             text-[0.62rem] font-bold bg-slate-100 text-slate-400">
                    <i class="bi bi-diagram-2" style="font-size:.55rem"></i>
                    {{ $role->children->count() }}
                </span>
            @endif
        </div>

        {{-- Action buttons --}}
        <div class="flex items-center gap-1 flex-shrink-0">
            <a href="{{ route('role.show', $role) }}" title="Detail"
               class="w-7 h-7 rounded-lg border border-slate-200 bg-white flex items-center justify-center
                      text-[0.78rem] text-sky-600 hover:border-sky-400 hover:bg-sky-50 transition-colors">
                <i class="bi bi-eye"></i>
            </a>
            <a href="{{ route('role.edit', $role) }}" title="Edit"
               class="w-7 h-7 rounded-lg border border-slate-200 bg-white flex items-center justify-center
                      text-[0.78rem] text-blue-600 hover:border-blue-400 hover:bg-blue-50 transition-colors">
                <i class="bi bi-pencil"></i>
            </a>
            <form method="POST" action="{{ route('role.destroy', $role) }}" style="display:contents"
                  onsubmit="return confirm('Hapus role {{ addslashes($role->name) }}?')">
                @csrf @method('DELETE')
                <button type="submit" title="Hapus"
                        class="w-7 h-7 rounded-lg border border-slate-200 bg-white flex items-center justify-center
                               text-[0.78rem] text-red-500 hover:border-red-400 hover:bg-red-50 transition-colors">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- ── Children — always start collapsed ──────────────────────────── --}}
    @if($hasChildren)
        <div class="rl-children collapsed">
            @foreach($role->children->sortBy('name') as $child)
                @include('role._tree_node', ['role' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif

</div>
