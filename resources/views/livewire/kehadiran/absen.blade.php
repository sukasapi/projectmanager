<div class="mx-auto max-w-2xl space-y-5" wire:poll.30s>
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Absensi</h1>
        <p class="text-sm text-slate-500">
            Jam kerja standar {{ $jamMasuk }}–{{ $jamPulang }} WIB.
            {{ \Illuminate\Support\Carbon::now($tz)->translatedFormat('l, d F Y') }}.
        </p>
    </div>

    @error('kehadiran')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        {{-- Status hari ini --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Status hari ini</p>
                @if ($kehadiran)
                    <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $kehadiran->status->color() }}">
                        {{ $kehadiran->status->label() }}
                    </span>
                    <span class="ml-1.5 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $kehadiran->work_mode->color() }}">
                        {{ $kehadiran->work_mode->label() }}
                    </span>
                @else
                    <p class="mt-1 text-sm text-slate-500">Belum absen</p>
                @endif
            </div>

            <div class="text-right">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Durasi kerja</p>
                <p class="mt-0.5 text-lg font-semibold tabular-nums text-brand-700">
                    @if ($kehadiran && $kehadiran->clock_out)
                        {{ intdiv($kehadiran->work_duration_minutes, 60) }}j {{ $kehadiran->work_duration_minutes % 60 }}m
                    @elseif ($kehadiran && $kehadiran->clock_in)
                        <span class="text-blue-600">berjalan…</span>
                    @else
                        —
                    @endif
                </p>
            </div>
        </div>

        {{-- Waktu masuk/pulang --}}
        <div class="grid grid-cols-2 gap-4 py-4">
            <div class="rounded-lg bg-slate-50 p-3 text-center">
                <p class="text-xs text-slate-400">Clock-in</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-slate-800">
                    {{ $kehadiran?->clock_in?->timezone($tz)->format('H:i') ?? '--:--' }}
                </p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 text-center">
                <p class="text-xs text-slate-400">Clock-out</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-slate-800">
                    {{ $kehadiran?->clock_out?->timezone($tz)->format('H:i') ?? '--:--' }}
                </p>
            </div>
        </div>

        {{-- Aksi --}}
        @if (! $kehadiran || ! $kehadiran->clock_in)
            {{-- Belum clock-in: pilih mode + tombol masuk --}}
            <div class="space-y-3 border-t border-slate-100 pt-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Mode kerja hari ini</label>
                    <select wire:model.live="mode"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @foreach ($daftarMode as $m)
                            <option value="{{ $m->value }}">{{ $m->label() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">Karyawan onsite boleh memilih offsite pada hari tertentu (dengan alasan).</p>
                </div>

                @if ($mode === \App\Enums\ModeKerja::OFFSITE->value)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Alasan offsite</label>
                        <input type="text" wire:model="alasan" placeholder="mis. kerja dari rumah, kendala transport"
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('alasan') border-red-400 @enderror">
                        @error('alasan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <button wire:click="clockIn" wire:loading.attr="disabled"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 disabled:opacity-60">
                    <svg wire:loading wire:target="clockIn" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Clock-in Sekarang
                </button>
            </div>
        @elseif (! $kehadiran->clock_out)
            {{-- Sudah masuk, belum pulang --}}
            <div class="border-t border-slate-100 pt-4">
                <button x-on:click="$confirm(@js('Akhiri jam kerja hari ini?'), { confirmText: 'Clock-out' }).then(ok => ok && $wire.clockOut())" wire:loading.attr="disabled"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 disabled:opacity-60">
                    <svg wire:loading wire:target="clockOut" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Clock-out Sekarang
                </button>
            </div>
        @else
            {{-- Selesai --}}
            <div class="border-t border-slate-100 pt-4 text-center text-sm text-slate-500">
                Kehadiran hari ini sudah lengkap. Terima kasih 👋
            </div>
        @endif
    </div>

    <a href="{{ route('kehadiran.riwayat') }}" wire:navigate
       class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800">
        Lihat riwayat kehadiran
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
    </a>
</div>
