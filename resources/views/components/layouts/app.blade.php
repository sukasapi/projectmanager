@php
    $user = auth()->user();
    $isAdmin = $user && $user->isSupervisory();        // Supervisor atau Super Admin
    $isSuperAdmin = $user && $user->isSuperAdmin();
    $punyaLogbook = in_array($user?->employment_type?->value, ['FREELANCE', 'INTERN'], true);
    // Pemantauan & Tim & Artis: Admin/Supervisor atau Team Lead (perwakilan supervisor, studio-wide).
    $bisaPemantauan = $user && $user->bisaPemantauan();
    // Team lead (peran, atau memimpin minimal satu episode) boleh melihat Timeline meski bukan supervisor.
    $isLeadAny = $isAdmin || ($user && $user->isTeamLead()) || ($user && \App\Models\Proyek::where('team_lead_id', $user->id)->exists());

    // Nav berkelompok. 'show' (opsional) menyembunyikan item dari pengguna tertentu.
    // Menu ringkas (permintaan 2026-07-09): Produksi 4 item, Monitoring 3 item.
    // Seri diatur dari halaman Episode; Shotlist dari Pra-Produksi; Kelola Aset dari Aset Management.
    $navGroups = [
        'Produksi' => [
            ['route' => 'proyek', 'label' => 'Episode', 'icon' => 'M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z'],
            ['route' => 'pra-produksi', 'label' => 'Pra-Produksi', 'icon' => 'M4 5h16M4 12h16M4 19h10'],
            ['route' => 'shot-matrix', 'label' => 'Produksi', 'icon' => 'M3 3h7v7H3V3zm0 11h7v7H3v-7zM14 3h7v7h-7V3zm0 11h7v7h-7v-7z'],
            ['route' => 'pasca-produksi', 'label' => 'Pasca-Produksi', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
        ],
        'Monitoring' => [
            ['route' => 'jadwal', 'label' => 'Timeline', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'show' => $isLeadAny],
            ['route' => 'aset', 'label' => 'Aset Management', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            // Log Book: supervisor/lead → review; freelance/intern → logbook harian.
            ['route' => $bisaPemantauan ? 'logbook.review' : 'logbook', 'label' => 'Log Book', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'show' => $punyaLogbook || $bisaPemantauan],
        ],
        'Tim & SDM' => [
            ['route' => 'tim', 'label' => 'Tim & Artis', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-2a3 3 0 10-3-3', 'show' => $bisaPemantauan],
            ['route' => 'kehadiran', 'label' => 'Absensi', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ],
        'Konfigurasi' => [
            ['route' => 'pengaturan', 'label' => 'Konfigurasi Website', 'icon' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 000 18M12.5 3a17 17 0 010 18', 'show' => $isSuperAdmin],
            ['route' => 'pengaturan.shotlist', 'label' => 'Pengaturan Shotlist', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'show' => $isSuperAdmin],
            ['route' => 'pengaturan.pipeline', 'label' => 'Konfigurasi Pipeline', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z', 'show' => $isSuperAdmin],
            ['route' => 'pengaturan.log', 'label' => 'Log Aplikasi', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'show' => $isSuperAdmin],
        ],
        'Pemantauan' => [
            ['route' => 'monitoring', 'label' => 'Monitoring', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'show' => $bisaPemantauan],
            ['route' => 'laporan-kehadiran', 'label' => 'Laporan Kehadiran', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'show' => $bisaPemantauan],
            ['route' => 'laporan-progress', 'label' => 'Laporan Progress', 'icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z', 'show' => $bisaPemantauan],
        ],
    ];

    $current = $header ?? 'Dashboard';
    foreach ($navGroups as $items) {
        foreach ($items as $i) {
            if (request()->routeIs($i['route'])) {
                $current = $i['label'];
            }
        }
    }
    if (request()->routeIs('dashboard')) $current = 'Dashboard';
    if (request()->routeIs('tugas-saya')) $current = 'Tugas Saya';
    if (request()->routeIs('seri')) $current = 'Seri';
    if (request()->routeIs('shotlist')) $current = 'Shotlist';
    if (request()->routeIs('aset-kelola')) $current = 'Kelola Aset';
    if (request()->routeIs('tim.detail')) $current = 'Detail Artis';
    if (request()->routeIs('kehadiran.riwayat')) $current = 'Riwayat Kehadiran';
    if (request()->routeIs('pengaturan')) $current = 'Pengaturan';

    // Absence gate: non-supervisor wajib absen dulu sebelum memakai aplikasi.
    $harusAbsen = $user && ! $isAdmin && ! \App\Models\Kehadiran::sudahTercatatHariIni($user->id);

    $perusahaan = \App\Models\Perusahaan::current();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ($current.' · '.$perusahaan->appName()) }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#16313f">
    <link rel="icon" href="/favicon2.ico?v=2" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon2.ico?v=2" type="image/x-icon">
    <link rel="apple-touch-icon" href="/icon.svg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">

{{-- Splashscreen: tampil sekali per sesi browser (logo + bar loading). --}}
@php $splashLogo = $perusahaan->logoUrl(); @endphp
<div x-data="{ loading: ! sessionStorage.getItem('splashShown') }"
     x-init="if (loading) setTimeout(() => { loading = false; sessionStorage.setItem('splashShown', '1'); }, 1300)"
     x-show="loading" x-cloak x-transition.opacity.duration.600ms
     class="fixed inset-0 z-[100] flex flex-col items-center justify-center gap-6 bg-brand-900">
    @if ($splashLogo)
        <img src="{{ $splashLogo }}" alt="{{ $perusahaan->appName() }}" class="h-24 w-auto animate-pulse">
    @else
        <div class="text-3xl font-bold tracking-tight text-white">{{ $perusahaan->appName() }}</div>
    @endif
    <div class="h-1.5 w-48 overflow-hidden rounded-full bg-white/15">
        <div class="h-full w-1/3 rounded-full bg-gold-400 animate-loadbar"></div>
    </div>
    <p class="text-xs font-medium uppercase tracking-widest text-white/60">Memuat…</p>
</div>

<div x-data="{ sidebarOpen: false }" class="min-h-screen">

    {{-- Overlay mobile --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         x-transition.opacity class="fixed inset-0 z-30 bg-brand-950/60 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-brand-900 text-brand-100 transition-transform duration-200 lg:translate-x-0">
        {{-- Workspace header --}}
        <div class="flex h-16 items-center gap-3 border-b border-white/10 px-4">
            @if ($perusahaan->logoUrl())
                <img src="{{ $perusahaan->logoUrl() }}" alt="logo" class="h-9 w-9 rounded-lg object-cover">
            @else
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-400 text-base font-bold text-brand-900">◑</span>
            @endif
            <div class="leading-tight">
                <p class="text-sm font-semibold text-white">{{ $perusahaan->appName() }}</p>
                <p class="text-[11px] text-brand-300">{{ $perusahaan->name }}</p>
            </div>
        </div>

        <nav class="flex-1 space-y-4 overflow-y-auto px-3 pb-4 pt-3">
            {{-- Dashboard (beranda, berdiri sendiri) --}}
            @php $dashAktif = request()->routeIs('dashboard'); @endphp
            <a href="{{ route('dashboard') }}" wire:navigate @click="sidebarOpen = false"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition
                      {{ $dashAktif ? 'bg-gold-400 text-brand-900 shadow-sm' : 'text-brand-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            {{-- Tugas Saya (pintasan pribadi, berdiri sendiri seperti Dashboard) --}}
            @php $tugasAktif = request()->routeIs('tugas-saya'); @endphp
            <a href="{{ route('tugas-saya') }}" wire:navigate @click="sidebarOpen = false"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition
                      {{ $tugasAktif ? 'bg-gold-400 text-brand-900 shadow-sm' : 'text-brand-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                Tugas Saya
            </a>

            @foreach ($navGroups as $grup => $items)
                @php $visible = array_filter($items, fn ($i) => $i['show'] ?? true); @endphp
                @if (! empty($visible))
                    <div class="space-y-1">
                        <p class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-brand-400">{{ $grup }}</p>
                        @foreach ($visible as $item)
                            @php $active = request()->routeIs($item['route']); @endphp
                            <a href="{{ route($item['route']) }}" wire:navigate @click="sidebarOpen = false"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition
                                      {{ $active ? 'bg-gold-400 text-brand-900 shadow-sm' : 'text-brand-200 hover:bg-white/10 hover:text-white' }}">
                                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                </svg>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-4 text-[11px] text-brand-400">
            {{ $perusahaan->footer() }}
        </div>
    </aside>

    {{-- Konten utama --}}
    <div class="lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                {{-- Breadcrumb --}}
                <nav class="flex items-center gap-1.5 text-sm">
                    <span class="text-slate-400">Workspace</span>
                    <span class="text-slate-300">/</span>
                    <span class="font-semibold text-slate-700">{{ $current }}</span>
                </nav>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                {{-- Search --}}
                <form method="GET" action="{{ route('cari') }}" class="relative hidden md:block">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M11 19a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                    <input type="search" name="q" placeholder="Cari episode, shot, artis…"
                           class="w-56 rounded-lg border-slate-200 bg-slate-50 pl-9 text-sm focus:border-brand-400 focus:bg-white focus:ring-brand-400">
                </form>

                @auth
                    <livewire:notifikasi.lonceng />

                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-lg px-1.5 py-1.5 hover:bg-slate-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                            <span class="hidden text-left sm:block">
                                <span class="block text-sm font-medium text-slate-700">{{ $user->name }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $user->employment_type?->label() }}</span>
                            </span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" x-cloak @click.outside="open = false" x-transition
                             class="absolute right-0 mt-2 w-52 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                            <div class="border-b border-slate-100 px-4 py-2.5">
                                <p class="text-sm font-medium text-slate-700">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $user->email }}</p>
                            </div>
                            <a href="{{ route('pengaturan') }}" wire:navigate
                               class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Pengaturan
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </header>

        <main class="p-4 sm:p-6">
            {{ $slot }}
        </main>
    </div>
</div>

@auth
    @if ($harusAbsen)
        {{-- Absence gate: modal tidak bisa ditutup; user harus clock-in dulu. --}}
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-brand-950/80 p-4 backdrop-blur-sm">
            @livewire('kehadiran.absen-gate')
        </div>
    @endif
@endauth

@auth
    {{-- Heartbeat presence ringan (Opsi C): ping kecil hanya saat tab terlihat.
         Update last_active_at untuk indikator "online sekarang" — tanpa daemon/WebSocket. --}}
    <script>
        (function () {
            const url = "{{ route('heartbeat') }}";
            const token = document.querySelector('meta[name=csrf-token]')?.content;
            if (!token) return;
            function ping() {
                if (document.visibilityState !== 'visible') return;
                fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                    keepalive: true,
                }).catch(function () {});
            }
            ping();
            setInterval(ping, {{ (int) config('kehadiran.heartbeat_interval_ms', 120000) }});
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') ping();
            });
        })();
    </script>
@endauth
@livewireScripts
</body>
</html>
