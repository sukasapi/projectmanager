<div class="space-y-5">
    {{-- Header / profil --}}
    <div>
        <a href="{{ route('tim') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Tim & Artis
        </a>
    </div>

    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-xl font-bold text-brand-700">{{ strtoupper(substr($artis->name, 0, 1)) }}</span>
        <div class="flex-1">
            <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ $artis->name }}</h1>
            <p class="text-sm text-slate-500">{{ $artis->email }} · {{ $artis->role ?? '—' }}@if ($artis->jabatan) · {{ $artis->jabatan }}@endif</p>
            <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                @if ($artis->phone)
                    <span class="inline-flex items-center gap-1"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>{{ $artis->phone }}</span>
                @endif
                @if ($artis->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $artis->whatsapp) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-green-600 hover:underline"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.86 9.86 0 004.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm5.8 14.13c-.24.68-1.42 1.31-1.95 1.36-.5.05-1.13.21-3.66-.77-3.08-1.21-5.04-4.34-5.2-4.54-.15-.2-1.24-1.65-1.24-3.15 0-1.5.79-2.24 1.07-2.54.28-.31.61-.38.81-.38h.58c.19 0 .44-.07.69.53.24.59.83 2.04.9 2.19.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.17-.31.39-.45.52-.15.15-.3.31-.13.61.17.3.76 1.25 1.63 2.02 1.12 1 2.07 1.31 2.37 1.46.3.15.47.12.64-.07.17-.2.74-.86.94-1.16.2-.3.39-.25.66-.15.27.1 1.71.81 2 .96.3.15.5.22.57.34.07.12.07.71-.17 1.39z"/></svg>{{ $artis->whatsapp }}</a>
                @endif
                @if ($artis->latitude && $artis->longitude)
                    <a href="https://www.google.com/maps?q={{ $artis->latitude }},{{ $artis->longitude }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-brand-600 hover:underline"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>peta</a>
                @endif
            </div>
            @if ($artis->address)
                <p class="mt-1 text-xs text-slate-400">{{ $artis->address }}</p>
            @endif
            <div class="mt-1.5 flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $artis->employment_type?->label() }}</span>
                @if ($artis->isOnline())
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-green-600"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>online</span>
                @elseif ($artis->last_active_at)
                    <span class="text-[11px] text-slate-400">aktif {{ $artis->last_active_at->timezone($tz)->diffForHumans() }}</span>
                @endif
                @unless ($artis->is_active)
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Nonaktif</span>
                @endunless
            </div>
        </div>
    </div>

    {{-- Ringkasan beban kerja --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @php
            $kartu = [
                ['Shot-task', $artis->tugas_shot_count],
                ['Pra-produksi', $artis->tugas_praproduksi_count],
                ['Aset', $artis->aset_count],
                ['Pasca-produksi', $artis->tugas_pascaproduksi_count],
            ];
        @endphp
        @foreach ($kartu as [$label, $nilai])
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-brand-700">{{ $nilai }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        {{-- Penugasan --}}
        <div class="space-y-5">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-semibold text-slate-700">Penugasan Shot</h2></div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($tugasShot as $t)
                        <li wire:key="ts-{{ $t->id }}" class="flex items-center justify-between px-4 py-2.5 text-sm">
                            <span class="text-slate-600">{{ $t->shot?->shot_code ?? '—' }} · {{ $t->tahap?->name }}</span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $t->status->color() }}">{{ $t->status->label() }}</span>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-xs text-slate-400">Tidak ada penugasan shot.</li>
                    @endforelse
                </ul>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-semibold text-slate-700">Tugas Lain</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($tugasPra as $t)
                        <li wire:key="pra-{{ $t->id }}" class="flex items-center justify-between px-4 py-2.5"><span class="text-slate-600">Pra · {{ $t->content_name }}</span><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $t->status->color() }}">{{ $t->status->label() }}</span></li>
                    @endforeach
                    @foreach ($aset as $t)
                        <li wire:key="aset-{{ $t->id }}" class="flex items-center justify-between px-4 py-2.5"><span class="text-slate-600">Aset · {{ $t->name }} ({{ $t->task->label() }})</span><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $t->status->color() }}">{{ $t->status->label() }}</span></li>
                    @endforeach
                    @foreach ($tugasPasca as $t)
                        <li wire:key="pasca-{{ $t->id }}" class="flex items-center justify-between px-4 py-2.5"><span class="text-slate-600">Pasca · {{ $t->task_type }}</span><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $t->status->color() }}">{{ $t->status->label() }}</span></li>
                    @endforeach
                    @if ($tugasPra->isEmpty() && $aset->isEmpty() && $tugasPasca->isEmpty())
                        <li class="px-4 py-6 text-center text-xs text-slate-400">Tidak ada tugas pra/aset/pasca.</li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Kehadiran & logbook --}}
        <div class="space-y-5">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-semibold text-slate-700">Kehadiran Terbaru</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($kehadiranTerbaru as $k)
                        <li wire:key="kh-{{ $k->id }}" class="flex items-center justify-between px-4 py-2.5">
                            <span class="text-slate-600">{{ $k->tanggal->timezone($tz)->translatedFormat('D, d M') }}</span>
                            <span class="flex items-center gap-1.5">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $k->status->color() }}">{{ $k->status->label() }}</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $k->work_mode->color() }}">{{ $k->work_mode->label() }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-xs text-slate-400">Belum ada kehadiran.</li>
                    @endforelse
                </ul>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-semibold text-slate-700">Logbook Terbaru</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($logbookTerbaru as $l)
                        <li wire:key="lb-{{ $l->id }}" class="px-4 py-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600">{{ $l->tanggal->timezone($tz)->translatedFormat('D, d M') }} · {{ intdiv($l->durasi_menit, 60) }}j {{ $l->durasi_menit % 60 }}m</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $l->status->color() }}">{{ $l->status->label() }}</span>
                            </div>
                            <p class="mt-0.5 line-clamp-1 text-xs text-slate-400">{{ $l->deskripsi }}</p>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-xs text-slate-400">Belum ada logbook.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
