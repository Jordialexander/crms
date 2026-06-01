<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - {{ $title ?? 'Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    @stack('css')
    <style>
        /* ── DataTables compat (masih dipakai view lain) ── */
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #e2e8f0; border-radius: .375rem; padding: .25rem .5rem;
            font-size: .8rem; outline: none;
        }
        .dataTables_wrapper .dataTables_filter input:focus,
        .dataTables_wrapper .dataTables_length select:focus { border-color: #3b82f6; }
        table.dataTable thead th { border-bottom: 1px solid #e2e8f0 !important; }

        /* ── Scrollbar sidebar ── */
        #sidebar-body::-webkit-scrollbar { width: 4px; }
        #sidebar-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 2px; }

        /* ── Status badges (dipakai di seluruh view) ── */
        .s-badge {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .65rem; font-weight: 700; letter-spacing: .03em;
            padding: .22rem .55rem; border-radius: 9999px; white-space: nowrap;
        }
        .s-badge::before {
            content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor;
        }
        .badge-draft              { background: #f1f5f9; color: #64748b; }
        .badge-need_review        { background: #e0f2fe; color: #0284c7; }
        .badge-under_review       { background: #fff7ed; color: #ea580c; }
        .badge-waiting_approval   { background: #f3f0ff; color: #7c3aed; }
        .badge-scheduled          { background: #ecfdf5; color: #059669; }
        .badge-unscheduled        { background: #fffbeb; color: #d97706; }
        .badge-rejected           { background: #fef2f2; color: #dc2626; }
        .badge-in_progress        { background: #eff6ff; color: #2563eb; }
        .badge-completed          { background: #f0fdf4; color: #16a34a; }
        .badge-failed             { background: #fef2f2; color: #b91c1c; }
        .badge-rollback           { background: #faf5ff; color: #9333ea; }
        .badge-closed             { background: #f8fafc; color: #475569; }
        .badge-closed_completed   { background: #f0fdf4; color: #15803d; }
        .badge-closed_failed      { background: #fef2f2; color: #b91c1c; }
        .badge-closed_rejected    { background: #fff1f2; color: #9f1239; }
        .badge-closed_canceled    { background: #f8fafc; color: #64748b; }
        .badge-canceled           { background: #fff1f2; color: #9f1239; }

        /* ── Shared table card (dipakai view lain) ── */
        .table-card {
            background: #fff; border-radius: .6rem;
            border: 1px solid #e8ecf2; overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,.02);
        }
        .table-card .tc-header {
            padding: .85rem 1.1rem;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-card table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .table-card thead th {
            background: #f8fafc; font-size: .68rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em; color: #94a3b8;
            padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9; border-top: none;
            text-align: left;
        }
        .table-card thead th.text-center,
        .table-card tbody td.text-center { text-align: center; }
        .table-card tbody tr { border-bottom: 1px solid #f8fafc; transition: background .1s; }
        .table-card tbody tr:hover { background: #fafbfd; }
        .table-card tbody tr:last-child { border-bottom: none; }
        .table-card tbody td { padding: .75rem 1rem; vertical-align: middle; border: none; font-size: .8rem; }
        .cr-number { font-weight: 700; font-size: .8rem; color: #2b6cb0; text-decoration: none; }
        .cr-number:hover { text-decoration: underline; color: #1e4e8c; }
        .cr-title  { font-size: .8rem; color: #334155; }
        .cr-date   { font-size: .72rem; color: #94a3b8; }
        .btn-xs {
            font-size: .71rem; padding: .28rem .65rem; border-radius: .35rem; font-weight: 600;
            border: 1px solid #e2e8f0; background: #fff; color: #475569; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: .3rem;
            transition: border-color .15s, color .15s;
        }
        .btn-xs:hover { border-color: #2b6cb0; color: #2b6cb0; }
        .text-accent { color: #2b6cb0; }
        .stat-card { border: none; border-radius: .75rem; }

        /* ── Cursor fix (Tailwind Preflight resets button cursor) ── */
        button { cursor: pointer; }

        /* ── Tab buttons (cr/show partials) ── */
        .tab-active   { color: #2563eb; font-weight: 600; border-bottom: 2px solid #3b82f6; }
        .tab-inactive { color: #64748b; border-bottom: 2px solid transparent; }
        .tab-inactive:hover { color: #334155; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 text-sm font-sans antialiased">

{{-- ═══════════════════════════════════════════════════ SIDEBAR ══ --}}
<aside class="fixed inset-y-0 left-0 w-[250px] z-50 flex flex-col
              bg-gradient-to-b from-slate-900 to-[#1a2e4a]
              shadow-[4px_0_20px_rgba(0,0,0,0.3)]">

    {{-- Brand --}}
    <div class="flex items-center gap-3 px-4 py-4 border-b border-white/[0.07] shrink-0">
        <div class="w-9 h-9 shrink-0 rounded-[10px] flex items-center justify-center
                    bg-gradient-to-br from-blue-500 to-blue-700
                    shadow-[0_4px_12px_rgba(59,130,246,0.4)]">
            <i class="bi bi-shield-check text-white text-lg leading-none"></i>
        </div>
        <div class="leading-tight">
            <span class="block text-white font-bold text-[0.875rem] tracking-tight">Change Request</span>
            <span class="text-white/40 text-[0.68rem]">Management System</span>
        </div>
    </div>

    {{-- Nav --}}
    <div id="sidebar-body" class="flex-1 overflow-y-auto py-2">
        @php
            $sidebarCtx = session('sidebar_context', '');
        @endphp
        <nav>
            {{-- ── Menu Utama ── --}}
            <p class="px-[1.1rem] pt-4 pb-1 text-[0.62rem] font-bold uppercase tracking-[.08em] text-white/30">
                Menu Utama
            </p>
            <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')" icon="speedometer2">
                Dashboard
            </x-nav-link>

            {{-- ── Change Request ── --}}
            @can('view change_request')
            <p class="px-[1.1rem] pt-4 pb-1 text-[0.62rem] font-bold uppercase tracking-[.08em] text-white/30">
                Change Request
            </p>
            <x-nav-link href="{{ route('cr.index') }}"
                :active="request()->routeIs('cr.index') || (request()->routeIs('cr.show','cr.edit') && !$sidebarCtx)"
                icon="file-earmark-text">
                Daftar CR
            </x-nav-link>
            @can('create change_request')
            <x-nav-link href="{{ route('cr.create') }}" :active="request()->routeIs('cr.create')" icon="plus-circle">
                Buat CR Baru
            </x-nav-link>
            @endcan
            @endcan

            {{-- ── Inbox ── --}}
            @can('view approval')
            @php
                $myId = auth()->id();
                $myInboxCounts = [
                    'need_review'      => \App\Models\ChangeRequest::where('status','need_review')->where('current_approver_id',$myId)->count(),
                    'under_review'     => \App\Models\ChangeRequest::where('status','under_review')->where('current_approver_id',$myId)->count(),
                    'waiting_approval' => \App\Models\ChangeRequest::where('status','waiting_approval')->where('current_approver_id',$myId)->count()
                                       + \App\Models\ChangeRequest::whereIn('status',['failed','rollback'])->whereJsonContains('approver_chain',$myId)->count(),
                ];
            @endphp
            <p class="px-[1.1rem] pt-4 pb-1 text-[0.62rem] font-bold uppercase tracking-[.08em] text-white/30">
                Inbox
            </p>
            <x-nav-link href="{{ route('need-review.index') }}"
                :active="request()->routeIs('need-review.*') || $sidebarCtx === 'need_review'"
                icon="eye" :badge="$myInboxCounts['need_review']" badge-color="red">
                Need Review
            </x-nav-link>
            <x-nav-link href="{{ route('approval.index', ['status'=>'under_review']) }}"
                :active="(request()->routeIs('approval.*') && (request()->query('status')==='under_review' || !request()->query('status'))) || $sidebarCtx === 'under_review'"
                icon="pencil-square" :badge="$myInboxCounts['under_review']" badge-color="yellow">
                Under Review
            </x-nav-link>
            <x-nav-link href="{{ route('approval.index', ['status'=>'waiting_approval']) }}"
                :active="(request()->routeIs('approval.*') && request()->query('status')==='waiting_approval') || $sidebarCtx === 'waiting_approval'"
                icon="hourglass" :badge="$myInboxCounts['waiting_approval']" badge-color="blue">
                Waiting Approval
            </x-nav-link>
            @endcan

            {{-- ── Jadwal ── --}}
            @can('view schedule')
            @php
                $isSchedulePage  = request()->routeIs('schedule.*');
                $scheduleTab     = request()->get('tab', 'unscheduled');
                $scheduleUser    = auth()->user();
                $scopeToPic      = !$scheduleUser->can('view all change_request') && !$scheduleUser->can('view team change_request');
                $scheduleCount   = fn(array $statuses) => \App\Models\ChangeRequest::whereIn('status', $statuses)
                    ->when($scopeToPic, fn($q) => $q->where(fn($q2) => $q2
                        ->where('pic_id', $scheduleUser->id)
                        ->orWhere('requester_id', $scheduleUser->id)
                        ->orWhereJsonContains('approver_chain', $scheduleUser->id)
                    ))
                    ->count();
                $scheduleCounts  = [
                    'unscheduled' => $scheduleCount(['approved']),
                    'scheduled'   => $scheduleCount(['scheduled']),
                    'in_progress' => $scheduleCount(['in_progress']),
                    'completed'   => $scheduleCount(['completed']),
                    'closed'      => $scheduleCount(['completed', 'failed', 'rollback', 'closed']),
                ];
            @endphp
            <p class="px-[1.1rem] pt-4 pb-1 text-[0.62rem] font-bold uppercase tracking-[.08em] text-white/30">
                Jadwal
            </p>
            <x-nav-link href="{{ route('schedule.index',['tab'=>'unscheduled']) }}"
                :active="$isSchedulePage && $scheduleTab==='unscheduled'" icon="calendar-plus"
                :badge="$scheduleCounts['unscheduled']" badge-color="yellow">
                Perlu Dijadwalkan
            </x-nav-link>
            <x-nav-link href="{{ route('schedule.index',['tab'=>'scheduled']) }}"
                :active="$isSchedulePage && $scheduleTab==='scheduled'" icon="calendar-check"
                :badge="$scheduleCounts['scheduled']" badge-color="blue">
                Dijadwalkan
            </x-nav-link>
            <x-nav-link href="{{ route('schedule.index',['tab'=>'in_progress']) }}"
                :active="$isSchedulePage && $scheduleTab==='in_progress'" icon="play-circle"
                :badge="$scheduleCounts['in_progress']" badge-color="blue">
                Sedang Dikerjakan
            </x-nav-link>
            <x-nav-link href="{{ route('schedule.index',['tab'=>'completed']) }}"
                :active="$isSchedulePage && $scheduleTab==='completed'" icon="archive"
                :badge="$scheduleCounts['completed']" badge-color="green">
                Selesai / Belum Ditutup
            </x-nav-link>
            @endcan

            {{-- ── Laporan ── --}}
            @can('view report')
            <p class="px-[1.1rem] pt-4 pb-1 text-[0.62rem] font-bold uppercase tracking-[.08em] text-white/30">
                Laporan
            </p>
            <x-nav-link href="{{ route('report.index') }}" :active="request()->routeIs('report.*')" icon="bar-chart">
                Laporan
            </x-nav-link>
            @endcan

            {{-- ── Administrasi ── --}}
            @canany(['view user','manage roles','manage approval_rules','manage cr_options'])
            <p class="px-[1.1rem] pt-4 pb-1 text-[0.62rem] font-bold uppercase tracking-[.08em] text-white/30">
                Administrasi
            </p>
            @can('view user')
            <x-nav-link href="{{ route('user.index') }}" :active="request()->routeIs('user.*')" icon="people">
                Manajemen User
            </x-nav-link>
            @endcan
            @can('manage roles')
            <x-nav-link href="{{ route('role.index') }}" :active="request()->routeIs('role.*')" icon="sliders">
                Kelola Roles
            </x-nav-link>
            @endcan
            {{-- @can('manage approval_rules')
            <x-nav-link href="{{ route('approval-rule.index') }}" :active="request()->routeIs('approval-rule.*')" icon="diagram-3">
                Approval Rules
            </x-nav-link>
            @endcan --}}
            @can('manage cr_options')
            <x-nav-link href="{{ route('cr-options.index', 'change_type') }}"
                :active="request()->routeIs('cr-options.*') && request()->route('type') === 'change_type'"
                icon="arrow-left-right">
                Tipe Change
            </x-nav-link>
            <x-nav-link href="{{ route('cr-options.index', 'category') }}"
                :active="request()->routeIs('cr-options.*') && request()->route('type') === 'category'"
                icon="folder2">
                Kategori
            </x-nav-link>
            <x-nav-link href="{{ route('cr-options.index', 'priority') }}"
                :active="request()->routeIs('cr-options.*') && request()->route('type') === 'priority'"
                icon="flag">
                Prioritas
            </x-nav-link>
            @endcan
            @endcanany
        </nav>
    </div>

    {{-- User footer --}}
    <div class="shrink-0 flex items-center gap-2.5 px-3.5 py-3 border-t border-white/[0.07]">
        <div class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center
                    bg-gradient-to-br from-blue-500 to-indigo-500 text-white text-xs font-bold">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0 leading-tight">
            <div class="text-slate-200 text-[0.8rem] font-semibold truncate">{{ auth()->user()->name }}</div>
            <div class="text-white/35 text-[0.68rem] truncate">{{ auth()->user()->getRoleNames()->first() }}</div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" title="Logout"
                class="text-white/35 hover:text-red-400 transition-colors p-1 rounded cursor-pointer bg-transparent border-0">
                <i class="bi bi-box-arrow-right text-base leading-none"></i>
            </button>
        </form>
    </div>
</aside>

{{-- ════════════════════════════════════════════ MAIN CONTENT ══ --}}
<div class="ml-[250px] min-h-screen flex flex-col">

    {{-- Topbar --}}
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200
                   flex items-center justify-between px-6 py-2.5 shrink-0">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb">
            <ol class="flex items-center gap-1.5 text-xs text-slate-400">
                @foreach($breadcrumbs ?? [] as $label => $url)
                    @if($loop->last)
                        <li class="text-slate-600 font-medium">{{ $label }}</li>
                    @else
                        <li>
                            <a href="{{ $url }}" class="hover:text-blue-600 transition-colors">{{ $label }}</a>
                        </li>
                        <li><i class="bi bi-chevron-right text-[0.6rem]"></i></li>
                    @endif
                @endforeach
            </ol>
        </nav>

        {{-- Notifikasi --}}
        <div class="relative" id="notif-wrapper">
            <button id="notif-bell"
                class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700
                       transition-colors focus:outline-none">
                <i class="bi bi-bell text-base leading-none"></i>
                @if(($topbarUnreadNotifications ?? 0) > 0)
                    <span id="notif-badge"
                        class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1
                               flex items-center justify-center
                               rounded-full bg-red-500 text-white text-[0.6rem] font-bold leading-none">
                        {{ $topbarUnreadNotifications }}
                    </span>
                @else
                    <span id="notif-badge"
                        class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1
                               hidden items-center justify-center
                               rounded-full bg-red-500 text-white text-[0.6rem] font-bold leading-none">
                    </span>
                @endif
            </button>

            {{-- Dropdown panel — hidden by default, toggled via JS --}}
            <div id="notif-panel" style="display:none"
                 class="absolute right-0 top-full mt-2 w-[360px] rounded-xl bg-white
                        shadow-xl border border-slate-200 overflow-hidden z-50">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
                    <span class="font-semibold text-slate-700 text-sm">Notifikasi</span>
                    <a href="{{ route('notifications.index') }}"
                       class="text-xs text-blue-600 hover:underline">Lihat semua</a>
                </div>

                <div id="notif-list" class="max-h-[360px] overflow-y-auto divide-y divide-slate-50">
                    @php
                        $metaFor = function ($n) {
                            $kind = data_get($n->data, 'kind');
                            return match ($kind) {
                                'cr_submitted'               => ['icon'=>'send-check',            'color'=>'text-sky-500',   'title'=>'CR Disubmit'],
                                'cr_need_review'             => ['icon'=>'eye',                   'color'=>'text-sky-500',   'title'=>'Perlu Review'],
                                'cr_under_review'            => ['icon'=>'shield-exclamation',    'color'=>'text-amber-500', 'title'=>'Sedang Direview'],
                                'cr_waiting_approval'        => ['icon'=>'hourglass-split',       'color'=>'text-blue-500',  'title'=>'Menunggu Approval'],
                                'cr_needs_approval'          => ['icon'=>'bell',                  'color'=>'text-amber-500', 'title'=>'Butuh Approval'],
                                'cr_approved'                => ['icon'=>'check-circle',          'color'=>'text-green-500', 'title'=>'CR Disetujui'],
                                'cr_rejected'                => ['icon'=>'x-circle',              'color'=>'text-red-500',   'title'=>'CR Ditolak'],
                                'cr_assigned_engineer'       => ['icon'=>'tools',                 'color'=>'text-blue-500',  'title'=>'Tugas Implementasi'],
                                'cr_scheduled'               => ['icon'=>'calendar-check',        'color'=>'text-blue-500',  'title'=>'CR Dijadwalkan'],
                                'cr_in_progress'             => ['icon'=>'play-circle',           'color'=>'text-amber-500', 'title'=>'Implementasi Berjalan'],
                                'cr_implementation_done'     => ['icon'=>'clipboard-check',       'color'=>'text-green-500', 'title'=>'Implementasi Berhasil'],
                                'cr_implementation_failed'   => ['icon'=>'clipboard-x',           'color'=>'text-red-500',   'title'=>'Implementasi Gagal'],
                                'cr_implementation_rollback' => ['icon'=>'arrow-counterclockwise','color'=>'text-amber-500', 'title'=>'Implementasi Rollback'],
                                'cr_rescheduled'             => ['icon'=>'calendar-x',            'color'=>'text-amber-500', 'title'=>'CR Dijadwalkan Ulang'],
                                'cr_post_mortem_filled'      => ['icon'=>'file-earmark-text',     'color'=>'text-slate-500', 'title'=>'Post-Mortem Diisi'],
                                'cr_closed'                  => ['icon'=>'lock',                  'color'=>'text-slate-500', 'title'=>'CR Ditutup'],
                                default                      => ['icon'=>'info-circle',           'color'=>'text-slate-400', 'title'=>'Notifikasi'],
                            };
                        };
                    @endphp

                    @forelse(($topbarNotifications ?? collect()) as $n)
                        @php
                            $m     = $metaFor($n);
                            $nKind = data_get($n->data, 'kind');
                            $crId  = data_get($n->data, 'cr_id');
                            $target = $crId
                                ? match($nKind) {
                                    'cr_need_review'      => route('need-review.show', $crId),
                                    'cr_under_review'     => route('cr.show', $crId),
                                    'cr_waiting_approval' => route('cr.show', $crId),
                                    'cr_needs_approval'   => route('approval.show', $crId),
                                    default               => route('cr.show', $crId),
                                  }
                                : route('notifications.index');
                        @endphp
                        <div class="notif-item flex items-start gap-3 px-4 py-3 {{ $n->read_at ? '' : 'bg-blue-50/50' }} hover:bg-slate-50 transition-colors"
                             data-notif-id="{{ $n->id }}" data-read="{{ $n->read_at ? '1' : '0' }}">
                            <i class="bi bi-{{ $m['icon'] }} {{ $m['color'] }} text-base mt-0.5 shrink-0"></i>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold text-slate-700">
                                    {{ $m['title'] }}
                                    <span class="notif-new-badge ml-1 inline-block text-[0.6rem] font-bold bg-blue-600 text-white px-1.5 py-0.5 rounded-full {{ $n->read_at ? 'hidden' : '' }}">NEW</span>
                                </div>
                                <div class="text-xs text-slate-500 truncate">
                                    {{ data_get($n->data,'cr_number') }} – {{ \Illuminate\Support\Str::limit((string)data_get($n->data,'title'), 42) }}
                                </div>
                                <div class="text-[0.67rem] text-slate-400 mt-0.5">{{ $n->created_at->diffForHumans() }}</div>
                            </div>
                            <a href="{{ $target }}"
                               class="notif-open-btn shrink-0 text-xs text-blue-600 border border-blue-200 rounded-md px-2 py-1
                                      hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-colors"
                               data-open-url="{{ route('notifications.open', $n->id) }}"
                               data-target="{{ $target }}">Buka</a>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-slate-400 text-xs">Tidak ada notifikasi.</div>
                    @endforelse
                </div>

                <div class="px-4 py-2.5 border-t border-slate-100">
                    <button id="notif-read-all-btn"
                        class="w-full text-xs text-slate-500 border border-slate-200 rounded-lg py-1.5
                               hover:bg-slate-50 hover:border-slate-300 transition-colors flex items-center justify-center gap-1.5">
                        <i class="bi bi-check2-all"></i> Tandai semua dibaca
                    </button>
                </div>
            </div>
        </div>
    </header>

    {{-- Page content --}}
    <main class="flex-1 p-6">

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="flash-msg flex items-center gap-2.5 bg-green-50 border border-green-200 text-green-800
                        rounded-xl px-4 py-3 mb-4 text-sm">
                <i class="bi bi-check-circle-fill text-green-500 shrink-0"></i>
                <span class="flex-1">{{ session('success') }}</span>
                <button onclick="this.closest('.flash-msg').remove()"
                        class="text-green-400 hover:text-green-600 ml-auto leading-none text-lg">&times;</button>
            </div>
        @endif
        @if(session('error'))
            <div class="flash-msg flex items-center gap-2.5 bg-red-50 border border-red-200 text-red-800
                        rounded-xl px-4 py-3 mb-4 text-sm">
                <i class="bi bi-exclamation-triangle-fill text-red-500 shrink-0"></i>
                <span class="flex-1">{{ session('error') }}</span>
                <button onclick="this.closest('.flash-msg').remove()"
                        class="text-red-400 hover:text-red-600 ml-auto leading-none text-lg">&times;</button>
            </div>
        @endif
        @if($errors->any())
            <div class="flash-msg bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 mb-4 text-sm">
                <div class="flex items-center gap-2 mb-1">
                    <i class="bi bi-exclamation-triangle-fill text-red-500 shrink-0"></i>
                    <strong>Terdapat kesalahan:</strong>
                    <button onclick="this.closest('.flash-msg').remove()"
                            class="text-red-400 hover:text-red-600 ml-auto leading-none text-lg">&times;</button>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-xs ml-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function updateBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
            badge.classList.add('flex');
        } else {
            badge.classList.add('hidden');
            badge.classList.remove('flex');
        }
    }

    function markItemRead(item) {
        if (item.dataset.read === '1') return;
        item.dataset.read = '1';
        item.classList.remove('bg-blue-50/50');
        const dot = item.querySelector('.notif-new-badge');
        if (dot) dot.classList.add('hidden');
    }

    document.querySelectorAll('.notif-open-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const item   = btn.closest('.notif-item');
            const target = btn.dataset.target;
            fetch(btn.dataset.openUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            }).then(() => {
                if (item) markItemRead(item);
                const unread = document.querySelectorAll('.notif-item[data-read="0"]').length;
                updateBadge(unread);
                window.location.href = target;
            }).catch(() => { window.location.href = target; });
        });
    });

    const readAllBtn = document.getElementById('notif-read-all-btn');
    if (readAllBtn) {
        readAllBtn.addEventListener('click', () => {
            fetch('{{ route('notifications.readAll') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            }).then(() => {
                document.querySelectorAll('.notif-item').forEach(markItemRead);
                updateBadge(0);
            });
        });
    }

    // Notification dropdown toggle
    const bell  = document.getElementById('notif-bell');
    const panel = document.getElementById('notif-panel');
    if (bell && panel) {
        bell.addEventListener('click', e => {
            e.stopPropagation();
            const isOpen = panel.style.display !== 'none';
            panel.style.display = isOpen ? 'none' : 'block';
        });
        document.addEventListener('click', e => {
            if (!panel.contains(e.target) && e.target !== bell) {
                panel.style.display = 'none';
            }
        });
    }
})();
</script>
@stack('js')
</body>
</html>
