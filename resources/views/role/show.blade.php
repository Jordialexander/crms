@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">Detail Role</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">{{ $role->name }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('role.index') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-medium
                  border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 transition-colors">
            <i class="bi bi-arrow-left text-xs leading-none"></i> Kembali
        </a>
        <a href="{{ route('role.edit', $role) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-semibold
                  bg-blue-600 text-white hover:bg-blue-700 transition-colors">
            <i class="bi bi-pencil text-xs leading-none"></i> Edit Role
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Left sidebar --}}
    <div class="flex flex-col gap-4">

        {{-- Info card --}}
        <x-form-card title="Informasi Role" icon="shield-check">
            <div class="space-y-3">
                <div>
                    <div class="text-[0.72rem] text-slate-400 mb-0.5">Nama Role</div>
                    <div class="text-[0.88rem] font-bold text-slate-800">{{ $role->name }}</div>
                </div>
                @if($role->description)
                    <div>
                        <div class="text-[0.72rem] text-slate-400 mb-0.5">Deskripsi</div>
                        <div class="text-[0.82rem] text-slate-700">{{ $role->description }}</div>
                    </div>
                @endif
                <div class="pt-2 border-t border-slate-100 grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-[0.72rem] text-slate-400 mb-0.5">Level Hierarchy</div>
                        <div class="text-[0.82rem] font-semibold text-slate-700">Level {{ $role->level }}</div>
                    </div>
                    <div>
                        <div class="text-[0.72rem] text-slate-400 mb-0.5">Total Users</div>
                        <div class="text-[0.82rem] font-semibold text-slate-700">{{ $role->users()->count() }} users</div>
                    </div>
                    @if($role->role_type)
                        <div>
                            <div class="text-[0.72rem] text-slate-400 mb-0.5">Role Type</div>
                            @php
                                $typeChip = match($role->role_type) {
                                    'engineer'  => 'bg-blue-50 text-blue-600',
                                    'approver'  => 'bg-violet-50 text-violet-600',
                                    'requester' => 'bg-green-50 text-green-700',
                                    default     => 'bg-slate-100 text-slate-500',
                                };
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold {{ $typeChip }}">
                                {{ strtoupper($role->role_type) }}
                            </span>
                        </div>
                    @endif
                    @if($role->parent)
                        <div>
                            <div class="text-[0.72rem] text-slate-400 mb-0.5">Parent Role</div>
                            <a href="{{ route('role.show', $role->parent) }}"
                               class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                                {{ $role->parent->name }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </x-form-card>

        {{-- Sub-roles card --}}
        @if($role->children->isNotEmpty())
            <x-form-card title="Sub-Roles ({{ $role->children->count() }})" icon="diagram-2">
                <div class="space-y-1 -mx-4 -mb-4">
                    @foreach($role->children as $child)
                        <a href="{{ route('role.show', $child) }}"
                           class="flex items-center justify-between px-4 py-2.5 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-b-0">
                            <div>
                                <div class="text-[0.82rem] font-semibold text-slate-800">{{ $child->name }}</div>
                                <div class="text-[0.7rem] text-slate-400">Level {{ $child->level }} · {{ $child->users()->count() }} users</div>
                            </div>
                            <i class="bi bi-chevron-right text-slate-300 text-xs"></i>
                        </a>
                    @endforeach
                </div>
            </x-form-card>
        @endif
    </div>

    {{-- Right main --}}
    <div class="lg:col-span-2 flex flex-col gap-4">

        {{-- Permissions card --}}
        <x-form-card title="Permissions ({{ count($role->abilities ?? []) }})" icon="key">
            @if(($role->abilities ?? []) !== [])
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($groupedAbilities as $category => $abilities)
                        <div class="bg-slate-50 rounded-lg overflow-hidden border border-slate-100">
                            <div class="px-3 py-2 border-b border-slate-100">
                                <span class="text-[0.72rem] font-bold text-blue-600 uppercase tracking-wide">{{ $category }}</span>
                            </div>
                            <div class="p-3 space-y-2">
                                @foreach($abilities as $ability)
                                    <div class="flex items-start gap-2">
                                        <i class="bi bi-check-circle-fill text-green-500 text-[0.72rem] mt-0.5 flex-shrink-0"></i>
                                        <div>
                                            <div class="text-[0.75rem] font-semibold text-slate-700">{{ $ability['key'] }}</div>
                                            <div class="text-[0.68rem] text-slate-400">{{ $ability['label'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-[0.82rem] text-amber-700">
                    <i class="bi bi-exclamation-triangle flex-shrink-0"></i>
                    Role ini belum memiliki permissions.
                    <a href="{{ route('role.edit', $role) }}" class="underline font-medium ml-1">Edit role</a> untuk menambahkan.
                </div>
            @endif
        </x-form-card>

        {{-- Users card --}}
        @if($role->users()->exists())
            <x-form-card title="Users dengan Role ini ({{ $role->users()->count() }})" icon="people">
                <div class="-mx-4 -mb-4">
                    <table class="w-full text-[0.78rem]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="text-left px-4 py-2 text-[0.72rem] font-semibold text-slate-500">Nama</th>
                                <th class="text-left px-4 py-2 text-[0.72rem] font-semibold text-slate-500">Username</th>
                                <th class="text-left px-4 py-2 text-[0.72rem] font-semibold text-slate-500">Email</th>
                                <th class="text-left px-4 py-2 text-[0.72rem] font-semibold text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($role->users()->take(5)->get() as $user)
                                <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-2.5 font-medium text-slate-800">{{ $user->name }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $user->username }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $user->email ?: '—' }}</td>
                                    <td class="px-4 py-2.5">
                                        @if($user->is_active)
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold bg-green-100 text-green-700">Aktif</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold bg-slate-100 text-slate-500">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($role->users()->count() > 5)
                        <div class="px-4 py-2 text-[0.72rem] text-slate-400">
                            … dan {{ $role->users()->count() - 5 }} user lainnya
                        </div>
                    @endif
                </div>
            </x-form-card>
        @endif

    </div>
</div>

@endsection
