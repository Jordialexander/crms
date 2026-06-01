@extends('layouts.app')

@section('content')

<div class="flex items-start justify-between mb-5">
    <div>
        <h1 class="text-[1.05rem] font-bold text-slate-900 leading-tight">Manajemen User</h1>
        <p class="text-[0.78rem] text-slate-400 mt-0.5">Kelola akun dan role pengguna sistem</p>
    </div>
    @can('create user')
    <a href="{{ route('user.create') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.82rem] font-semibold
              bg-blue-600 text-white hover:bg-blue-700 transition-colors">
        <i class="bi bi-person-plus text-xs leading-none"></i> Tambah User
    </a>
    @endcan
</div>

{{-- Search --}}
<div class="bg-white border border-slate-200 rounded-xl px-4 py-3 mb-3 shadow-sm">
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari nama, username, email..."
               class="flex-1 text-[0.82rem] border border-slate-200 rounded-lg px-3 py-2
                      focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10
                      placeholder:text-slate-400">
        <button type="submit"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-[0.82rem] font-semibold
                       bg-blue-600 text-white hover:bg-blue-700 transition-colors">
            <i class="bi bi-search text-xs"></i> Cari
        </button>
        @if(request('search'))
        <a href="{{ route('user.index') }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-[0.82rem] font-medium
                  border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 transition-colors">
            Reset
        </a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
    <table class="w-full text-[0.82rem]">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-[0.72rem] font-semibold text-slate-500 uppercase tracking-wide">
                <th class="px-4 py-2.5 text-left">Nama</th>
                <th class="px-4 py-2.5 text-left">Username</th>
                <th class="px-4 py-2.5 text-left">Email</th>
                <th class="px-4 py-2.5 text-left">Role</th>
                <th class="px-4 py-2.5 text-left">Status</th>
                <th class="px-4 py-2.5 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            @forelse($users as $user)
            <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-2.5 font-semibold text-slate-800">{{ $user->name }}</td>
                <td class="px-4 py-2.5 font-mono text-slate-500 text-[0.78rem]">{{ $user->username }}</td>
                <td class="px-4 py-2.5 text-slate-600">{{ $user->email ?? '-' }}</td>
                <td class="px-4 py-2.5">
                    @foreach($user->getRoleNames() as $role)
                    @php
                        $roleColor = match($role) {
                            'admin'     => 'bg-red-100 text-red-700',
                            'approver'  => 'bg-violet-100 text-violet-700',
                            'engineer'  => 'bg-sky-100 text-sky-700',
                            'requester' => 'bg-slate-100 text-slate-600',
                            default     => 'bg-slate-100 text-slate-600',
                        };
                    @endphp
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold {{ $roleColor }}">
                        {{ strtoupper($role) }}
                    </span>
                    @endforeach
                </td>
                <td class="px-4 py-2.5">
                    @if($user->is_active)
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold bg-green-100 text-green-700">Aktif</span>
                    @else
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold bg-slate-100 text-slate-500">Nonaktif</span>
                    @endif
                </td>
                <td class="px-4 py-2.5">
                    <div class="flex gap-1">
                        @can('edit user')
                        <a href="{{ route('user.edit', $user) }}"
                           class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-slate-200
                                  text-slate-500 hover:border-slate-300 hover:bg-slate-50 transition-colors">
                            <i class="bi bi-pencil text-xs"></i>
                        </a>
                        @endcan
                        @can('delete user')
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('user.destroy', $user) }}"
                              onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                            @csrf @method('DELETE')
                            <button class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-red-200
                                           text-red-500 hover:border-red-300 hover:bg-red-50 transition-colors">
                                <i class="bi bi-trash text-xs"></i>
                            </button>
                        </form>
                        @endif
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-[0.82rem] text-slate-400">
                    <i class="bi bi-people text-2xl block mb-2 opacity-30"></i>
                    Tidak ada user ditemukan
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())
    <div class="px-4 py-3 border-t border-slate-100">{{ $users->links() }}</div>
    @endif
</div>

@endsection
