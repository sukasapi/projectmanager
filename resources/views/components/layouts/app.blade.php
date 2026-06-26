@php
    $user = auth()->user();
    $isAdmin = $user && $user->role === 'Supervisor';
    $punyaLogbook = in_array($user?->employment_type?->value, ['FREELANCE', 'INTERN'], true);

    // Nav berkelompok. 'show' (opsional) menyembunyikan item dari pengguna tertentu.
    $navGroups = [
        'Produksi' => [
            ['route' => 'proyek', 'label' => 'Episode', 'icon' => 'M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z'],
            ['route' => 'shot-matrix', 'label' => 'Shot Matrix', 'icon' => 'M3 3h7v7H3V3zm0 11h7v7H3v-7zM14 3h7v7h-7V3zm0 11h7v7h-7v-7z'],
            ['route' => 'pra-produksi', 'label' => 'Pra-Produksi', 'icon' => 'M4 5h16M4 12h16M4 19h10'],
            ['route' => 'aset', 'label' => 'Asset Library', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['route' => 'pasca-produksi', 'label' => 'Pasca-Produksi', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
        ],
        'Tim & SDM' => [
            ['route' => 'tim', 'label' => 'Tim & Artis', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-2a3 3 0 10-3-3'],
            ['route' => 'kehadiran', 'label' => 'Absensi', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['route' => 'logbook', 'label' => 'Logbook', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'show' => $punyaLogbook],
        ],
        'Pemantauan' => [
            ['route' => 'monitoring', 'label' => 'Monitoring', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'show' => $isAdmin],
            ['route' => 'logbook.review', 'label' => 'Review Logbook', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'show' => $isAdmin],
            ['route' => 'laporan-kehadiran', 'label' => 'Laporan', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'show' => $isAdmin],
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
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ($current.' · AnimTrack') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
<div x-data="{ sidebarOpen: false }" class="min-h-screen">

    {{-- Overlay mobile --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         x-transition.opacity class="fixed inset-0 z-30 bg-brand-950/60 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-brand-900 text-brand-100 transition-transform duration-200 lg:translate-x-0">
        {{-- Workspace header --}}
        <div class="flex h-16 items-center gap-3 border-b border-white/10 px-4">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-400 text-base font-bold text-brand-900">◑</span>
            <div class="leading-tight">
                <p class="text-sm font-semibold text-white">AnimTrack</p>
                <p class="text-[11px] text-brand-300">Owlorix Creative Lab</p>
            </div>
        </div>

        {{-- Quick add --}}
        <div class="px-3 pt-4">
            <a href="{{ route('proyek') }}" wire:navigate
               class="flex items-center justify-center gap-2 rounded-lg bg-gold-400 px-3 py-2 text-sm font-semibold text-brand-900 transition hover:bg-gold-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Baru
            </a>
        </div>

        <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-4">
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
            © {{ date('Y') }} Owlorix Creative Lab
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
                <div class="relative hidden md:block">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M11 19a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                    <input type="search" placeholder="Cari…"
                           class="w-56 rounded-lg border-slate-200 bg-slate-50 pl-9 text-sm focus:border-brand-400 focus:bg-white focus:ring-brand-400">
                </div>

                @auth
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
                            <form method="POST" action="{{ route('logout') }}">
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
