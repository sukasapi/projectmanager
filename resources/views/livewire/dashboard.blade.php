<div class="space-y-5">
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Halo, {{ $user->name }} 👋</h1>
        <p class="text-sm text-slate-500">{{ \Illuminate\Support\Carbon::now($tz)->translatedFormat('l, d F Y') }} · ringkasan produksi & kehadiran Anda.</p>
    </div>

    {{-- Pemberitahuan: notifikasi belum dibaca --}}
    @if ($notifBelum > 0)
        <a href="{{ route('notifikasi') }}" wire:navigate
           class="flex items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 shadow-sm transition hover:bg-brand-100">
            <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $notifBelum > 9 ? '9+' : $notifBelum }}</span>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-brand-900">Anda punya {{ $notifBelum }} notifikasi belum dibaca</p>
                <p class="text-xs text-brand-700">Klik untuk melihat daftar notifikasi.</p>
            </div>
            <svg class="h-5 w-5 shrink-0 text-brand-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
        </a>
    @endif

    {{-- Status kehadiran hari ini --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <div>
                <p class="text-xs text-slate-400">Kehadiran hari ini</p>
                @if ($kehadiranHariIni)
                    <div class="mt-0.5 flex items-center gap-1.5">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $kehadiranHariIni->status->color() }}">{{ $kehadiranHariIni->status->label() }}</span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $kehadiranHariIni->work_mode->color() }}">{{ $kehadiranHariIni->work_mode->label() }}</span>
                        <span class="text-xs text-slate-400">masuk {{ $kehadiranHariIni->clock_in?->timezone($tz)->format('H:i') ?? '—' }}</span>
                    </div>
                @else
                    <p class="mt-0.5 text-sm font-medium text-slate-600">Belum absen hari ini</p>
                @endif
            </div>
        </div>
        <a href="{{ route('kehadiran') }}" wire:navigate class="rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
            {{ $kehadiranHariIni && ! $kehadiranHariIni->clock_out ? 'Buka Absensi' : 'Ke Absensi' }}
        </a>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @php
            $stat = [
                ['Episode aktif', $statProyekAktif, route('proyek'), 'text-brand-700'],
                ['Shot dikerjakan', $statDikerjakan, route('shot-matrix'), 'text-blue-700'],
                ['Menunggu review', $statReview, route('shot-matrix'), 'text-amber-700'],
                ['Tugas saya', $tugasSaya->count(), null, 'text-slate-800'],
            ];
        @endphp
        @foreach ($stat as [$label, $nilai, $url, $warna])
            <a @if ($url) href="{{ $url }}" wire:navigate @endif class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition {{ $url ? 'hover:border-brand-300 hover:shadow' : '' }}">
                <p class="text-xs text-slate-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums {{ $warna }}">{{ $nilai }}</p>
            </a>
        @endforeach
    </div>

    {{-- Ringkasan admin --}}
    @if ($isAdmin)
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-brand-200 bg-brand-50/50 p-4 text-sm">
            <span class="font-medium text-brand-800">Panel Supervisor:</span>
            <span class="text-slate-600">{{ $hadirHariIni }} kehadiran tercatat hari ini</span>
            <span class="text-slate-300">·</span>
            <span class="text-slate-600">{{ $logbookMenunggu }} logbook menunggu review</span>
            <a href="{{ route('monitoring') }}" wire:navigate class="ml-auto rounded-lg bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-800">Buka Monitoring</a>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        {{-- Tugas saya --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-semibold text-slate-700">Tugas Saya</h2></div>
            <ul class="divide-y divide-slate-100">
                @forelse ($tugasSaya as $t)
                    <li wire:key="my-{{ $t->id }}" class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="text-slate-600">{{ $t->shot?->shot_code ?? '—' }} · {{ $t->tahap?->name }}</span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $t->status->color() }}">{{ $t->status->label() }}</span>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-xs text-slate-400">Tidak ada tugas shot aktif untuk Anda. 🎉</li>
                @endforelse
            </ul>
        </div>

        {{-- Aktivitas terbaru --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-semibold text-slate-700">Aktivitas Terbaru</h2></div>
            <ul class="divide-y divide-slate-100">
                @forelse ($aktivitas as $r)
                    <li wire:key="act-{{ $r->id }}" class="px-4 py-2.5 text-xs">
                        <div class="font-medium text-slate-700">{{ $r->tugasShot?->shot?->shot_code ?? '—' }} <span class="text-slate-400">/ {{ $r->tugasShot?->tahap?->name }}</span></div>
                        <div class="mt-0.5 text-slate-500">{{ $r->author?->name ?? 'Sistem' }} · {{ $r->status_from }} → <span class="font-medium text-brand-700">{{ $r->status_to }}</span> · {{ $r->created_at?->timezone($tz)->diffForHumans() }}</div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-xs text-slate-400">Belum ada aktivitas.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
