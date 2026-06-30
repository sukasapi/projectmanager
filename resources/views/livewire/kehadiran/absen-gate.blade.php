<div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </span>
        <div>
            <h2 class="text-base font-bold text-slate-900">Absen Dulu untuk Mulai</h2>
            <p class="text-xs text-slate-500">Anda belum tercatat hadir hari ini. Clock-in untuk membuka aplikasi.</p>
        </div>
    </div>

    <p class="mb-4 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
        Jam kerja {{ $jamMasuk }}–{{ $jamPulang }} WIB ·
        {{ \Illuminate\Support\Carbon::now($tz)->translatedFormat('l, d F Y') }}
    </p>

    @error('kehadiran')
        <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="space-y-3">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Mode kerja hari ini</label>
            <select wire:model.live="mode" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                @foreach ($daftarMode as $m)<option value="{{ $m->value }}">{{ $m->label() }}</option>@endforeach
            </select>
        </div>

        @if ($mode === \App\Enums\ModeKerja::OFFSITE->value)
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Alasan offsite</label>
                <input type="text" wire:model="alasan" placeholder="mis. kerja dari rumah"
                       class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('alasan') border-red-400 @enderror">
                @error('alasan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <button wire:click="clockIn" wire:loading.attr="disabled" wire:target="clockIn"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 disabled:opacity-60">
            <svg wire:loading wire:target="clockIn" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            Clock-in Sekarang
        </button>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-xs text-slate-400 hover:text-slate-600">Keluar</button>
        </form>
    </div>
</div>
