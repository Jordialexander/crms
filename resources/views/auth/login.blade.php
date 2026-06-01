<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Change Request System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="min-h-screen flex items-center justify-center font-sans antialiased relative overflow-hidden
             bg-gradient-to-br from-slate-900 via-[#1e3a5f] to-slate-900">

    {{-- Decorative glow blobs --}}
    <div class="pointer-events-none absolute -top-48 -right-24 w-[600px] h-[600px] rounded-full
                bg-blue-500/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-36 -left-24 w-[500px] h-[500px] rounded-full
                bg-emerald-500/8 blur-3xl"></div>

    <div class="relative z-10 w-full max-w-[920px] mx-4">
        <div class="flex rounded-2xl overflow-hidden border border-white/[0.08]
                    shadow-[0_25px_80px_rgba(0,0,0,0.5)]">

            {{-- ── Left brand panel ── --}}
            <div class="hidden md:flex flex-col items-center justify-center w-[40%]
                        bg-gradient-to-b from-[#1a3a5c] to-[#0f2440] px-8 py-14
                        relative overflow-hidden">

                {{-- Subtle dot-grid pattern --}}
                <div class="absolute inset-0 opacity-[0.025]"
                     style="background-image:radial-gradient(#fff 1px,transparent 1px);background-size:20px 20px"></div>

                <div class="relative z-10 flex flex-col items-center text-white text-center">
                    {{-- Icon --}}
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6
                                bg-gradient-to-br from-blue-500 to-blue-700
                                shadow-[0_8px_30px_rgba(59,130,246,0.45)]">
                        <i class="bi bi-shield-check text-4xl leading-none"></i>
                    </div>

                    <h2 class="text-[1.1rem] font-bold leading-snug tracking-wide mb-1">
                        Change Request<br>Management System
                    </h2>

                    <div class="w-10 h-0.5 my-4 bg-gradient-to-r from-transparent via-blue-400/70 to-transparent rounded-full"></div>

                    <ul class="space-y-2.5 text-left w-full">
                        <li class="flex items-center gap-2.5 text-[0.78rem] text-white/60">
                            <i class="bi bi-check2-circle text-blue-400 shrink-0"></i>
                            Kelola permintaan perubahan
                        </li>
                    </ul>

                    <p class="mt-8 text-[0.7rem] text-white/30">© {{ date('Y') }} All rights reserved</p>
                </div>
            </div>

            {{-- ── Right form panel ── --}}
            <div class="flex-1 bg-white px-10 py-12">
                <h1 class="text-2xl font-extrabold text-slate-900 mb-1">Selamat Datang</h1>
                <p class="text-sm text-slate-500 mb-8">Masuk ke akun Anda untuk melanjutkan</p>

                {{-- Error alerts --}}
                @if($errors->any())
                    <div class="flex items-center gap-2.5 bg-red-50 border border-red-200 text-red-700
                                rounded-xl px-4 py-3 mb-5 text-sm">
                        <i class="bi bi-exclamation-triangle-fill text-red-500 shrink-0"></i>
                        {{ $errors->first() }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="flex items-center gap-2.5 bg-red-50 border border-red-200 text-red-700
                                rounded-xl px-4 py-3 mb-5 text-sm">
                        <i class="bi bi-exclamation-triangle-fill text-red-500 shrink-0"></i>
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('authenticate') }}" class="space-y-5">
                    @csrf

                    {{-- Username --}}
                    <div>
                        <label class="block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500 mb-1.5">
                            Username
                        </label>
                        <div class="flex rounded-lg border border-slate-200 focus-within:border-blue-500
                                    focus-within:ring-2 focus-within:ring-blue-500/15 transition-all overflow-hidden">
                            <span class="flex items-center px-3 bg-slate-50 border-r border-slate-200 text-slate-400">
                                <i class="bi bi-person text-base leading-none"></i>
                            </span>
                            <input type="text" name="username"
                                   placeholder="Masukkan username"
                                   value="{{ old('username') }}"
                                   autofocus required
                                   class="flex-1 px-3 py-2.5 text-sm text-slate-800 bg-slate-50
                                          focus:bg-white focus:outline-none placeholder:text-slate-400
                                          @error('username') border-red-400 @enderror">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500 mb-1.5">
                            Password
                        </label>
                        <div class="flex rounded-lg border border-slate-200 focus-within:border-blue-500
                                    focus-within:ring-2 focus-within:ring-blue-500/15 transition-all overflow-hidden">
                            <span class="flex items-center px-3 bg-slate-50 border-r border-slate-200 text-slate-400">
                                <i class="bi bi-lock text-base leading-none"></i>
                            </span>
                            <input type="password" name="password"
                                   placeholder="Masukkan password"
                                   required
                                   class="flex-1 px-3 py-2.5 text-sm text-slate-800 bg-slate-50
                                          focus:bg-white focus:outline-none placeholder:text-slate-400">
                        </div>
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="remember" id="remember"
                               class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer">
                        <label for="remember" class="text-sm text-slate-500 cursor-pointer select-none">
                            Ingat saya
                        </label>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2
                                   bg-gradient-to-r from-blue-700 to-blue-500 hover:from-blue-600 hover:to-blue-400
                                   text-white font-semibold text-[0.95rem] py-2.5 rounded-lg
                                   shadow-[0_4px_15px_rgba(59,130,246,0.35)]
                                   hover:shadow-[0_6px_20px_rgba(59,130,246,0.45)]
                                   hover:-translate-y-px active:translate-y-0
                                   transition-all duration-150">
                        <i class="bi bi-box-arrow-in-right text-base leading-none"></i>
                        Masuk
                    </button>
                </form>
            </div>

        </div>
    </div>
</body>
</html>
