<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Riwayat Kehadiran</h1>
            <p class="text-sm text-slate-500">Rekam jejak absensi pribadi Anda per bulan.</p>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Bulan</label>
            <input type="month" wire:model.live="bulan"
                   class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-slate-400">Hari masuk</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-slate-800">{{ $jumlahMasuk }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-slate-400">Total jam kerja</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-brand-700">{{ intdiv($totalMenit, 60) }}j {{ $totalMenit % 60 }}m</p>
        </div>
        <div class="col-span-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:col-span-1">
            <p class="text-xs text-slate-400">Hari tercatat</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-slate-800">{{ $riwayat->count() }}</p>
        </div>
    </div>

    {{-- Tabel --}}
    @if ($riwayat->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            Belum ada kehadiran tercatat pada bulan ini.
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500">
                            <th class="px-4 py-2.5 text-left">Tanggal</th>
                            <th class="px-4 py-2.5 text-left">Status</th>
                            <th class="px-4 py-2.5 text-left">Mode</th>
                            <th class="px-4 py-2.5 text-center">Masuk</th>
                            <th class="px-4 py-2.5 text-center">Pulang</th>
                            <th class="px-4 py-2.5 text-right">Durasi</th>
                            <th class="px-4 py-2.5 text-left">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($riwayat as $k)
                            <tr wire:key="hadir-{{ $k->id }}" class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $k->tanggal->timezone($tz)->translatedFormat('D, d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $k->status->color() }}">{{ $k->status->label() }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $k->work_mode->color() }}">{{ $k->work_mode->label() }}</span>
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums text-slate-600">{{ $k->clock_in?->timezone($tz)->format('H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-center tabular-nums text-slate-600">{{ $k->clock_out?->timezone($tz)->format('H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-medium text-slate-700">
                                    {{ $k->work_duration_minutes ? intdiv($k->work_duration_minutes, 60).'j '.($k->work_duration_minutes % 60).'m' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-400">{{ $k->catatan ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
