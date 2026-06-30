<div class="space-y-5" wire:poll.30s>
    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Monitoring</h1>
            <p class="text-sm text-slate-500">
                Pemantauan kehadiran & aktivitas — {{ \Illuminate\Support\Carbon::parse($hari)->translatedFormat('l, d F Y') }}.
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
            </span>
            {{ $online->count() }} online sekarang
        </span>
    </div>

    {{-- Kartu statistik --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @php
            $stat = [
                ['Hadir', $jmlHadir, 'text-green-700'],
                ['Terlambat', $jmlTerlambat, 'text-amber-700'],
                ['Alpha', $jmlAlpha, 'text-red-700'],
                ['Onsite', $jmlOnsite, 'text-green-700'],
                ['Offsite', $jmlOffsite, 'text-blue-700'],
                ['Logbook ⏳', $logbookMenunggu, 'text-amber-700'],
            ];
        @endphp
        @foreach ($stat as [$label, $nilai, $warna])
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums {{ $warna }}">{{ $nilai }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Kehadiran hari ini (per pengguna) --}}
        <div class="lg:col-span-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-700">Kehadiran Hari Ini</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-xs font-semibold text-slate-500">
                            <th class="px-4 py-2 text-left">Artis</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Mode</th>
                            <th class="px-4 py-2 text-center">Masuk</th>
                            <th class="px-4 py-2 text-center">Pulang</th>
                            <th class="px-4 py-2 text-center">Presence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($pengguna as $u)
                            @php $k = $sudahAbsen->get($u->id); @endphp
                            <tr wire:key="mon-{{ $u->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                        <div class="leading-tight">
                                            <div class="font-medium text-slate-700">{{ $u->name }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $u->employment_type?->label() }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    @if ($k)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $k->status->color() }}">{{ $k->status->label() }}</span>
                                    @else
                                        <span class="text-[11px] text-slate-400">belum absen</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    @if ($k)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $k->work_mode->color() }}">{{ $k->work_mode->label() }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-center tabular-nums text-slate-600">{{ $k?->clock_in?->timezone($tz)->format('H:i') ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-center tabular-nums text-slate-600">{{ $k?->clock_out?->timezone($tz)->format('H:i') ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    @if ($u->isOnline())
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-green-600"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>online</span>
                                    @elseif ($u->last_active_at)
                                        <span class="text-[11px] text-slate-400" title="{{ $u->last_active_at->timezone($tz)->format('d M H:i') }}">{{ $u->last_active_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-[11px] text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bukti kerja: progres tugas terbaru --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-700">Progres Tugas Terbaru</h2>
                <p class="text-[11px] text-slate-400">Bukti kerja nyata (transisi & revisi).</p>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($progresTerbaru as $r)
                    <li wire:key="rev-{{ $r->id }}" class="px-4 py-2.5 text-xs">
                        <div class="font-medium text-slate-700">
                            {{ $r->tugasShot?->shot?->shot_code ?? '—' }}
                            <span class="text-slate-400">/ {{ $r->tugasShot?->tahap?->name }}</span>
                        </div>
                        <div class="mt-0.5 text-slate-500">
                            {{ $r->author?->name ?? 'Sistem' }} ·
                            {{ $r->status_from }} → <span class="font-medium text-brand-700">{{ $r->status_to }}</span>
                        </div>
                        <div class="text-[11px] text-slate-400">{{ $r->created_at?->timezone($tz)->diffForHumans() }}</div>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-xs text-slate-400">Belum ada aktivitas.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <p class="text-[11px] text-slate-400">
        Pemantauan berbasis <span class="font-medium">presence + output</span> (clock-in, logbook, progres tugas) — bukan rekaman layar. Lihat ABSENSI.md §0.
    </p>
</div>
