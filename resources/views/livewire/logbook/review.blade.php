<div class="space-y-5" wire:poll.30s>
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Review Logbook</h1>
        <p class="text-sm text-slate-500">{{ $menunggu->count() }} entri menunggu persetujuan.</p>
    </div>

    @error('review')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @if ($menunggu->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            Tidak ada logbook yang menunggu review. 🎉
        </div>
    @else
        <div class="space-y-3">
            @foreach ($menunggu as $e)
                <div wire:key="rev-{{ $e->id }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">
                                    {{ strtoupper(substr($e->pengguna->name, 0, 1)) }}
                                </span>
                                <span class="text-sm font-semibold text-slate-800">{{ $e->pengguna->name }}</span>
                                <span class="text-xs text-slate-400">{{ $e->pengguna->employment_type?->label() }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">
                                {{ $e->tanggal->timezone($tz)->translatedFormat('D, d M Y') }} ·
                                {{ $e->jam_mulai->timezone($tz)->format('H:i') }}–{{ $e->jam_selesai->timezone($tz)->format('H:i') }} ·
                                <span class="font-medium text-slate-600">{{ intdiv($e->durasi_menit, 60) }}j {{ $e->durasi_menit % 60 }}m</span>
                                @if ($e->tugasShot?->shot)
                                    · {{ $e->tugasShot->shot->shot_code }} / {{ $e->tugasShot->tahap?->name }}
                                @endif
                            </p>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $e->deskripsi }}</p>
                            @if ($e->output_url)
                                <a href="{{ $e->output_url }}" target="_blank" rel="noopener" class="mt-1 inline-block text-xs text-brand-600 hover:underline">lihat output ↗</a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-end gap-2 border-t border-slate-100 pt-3">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text" wire:model="catatan.{{ $e->id }}" placeholder="Catatan review (opsional)"
                                   class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <button x-on:click="$confirm(@js('Tolak entri logbook ini?'), { danger: true }).then(ok => ok && $wire.tolak({{ $e->id }}))"
                                class="rounded-lg bg-red-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-red-700">Tolak</button>
                        <button wire:click="setujui({{ $e->id }})"
                                class="rounded-lg bg-green-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-green-700">Setujui</button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
