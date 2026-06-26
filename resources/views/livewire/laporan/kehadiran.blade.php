<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Laporan Kehadiran</h1>
            <p class="text-sm text-slate-500">Rekap per artis — {{ $periode }}.</p>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Periode</label>
            <input type="month" wire:model.live="bulan"
                   class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500">
                        <th class="px-4 py-2.5 text-left">Artis</th>
                        <th class="px-4 py-2.5 text-left">Tipe</th>
                        <th class="px-4 py-2.5 text-right">Hadir</th>
                        <th class="px-4 py-2.5 text-right">Terlambat</th>
                        <th class="px-4 py-2.5 text-right">Alpha</th>
                        <th class="px-4 py-2.5 text-right">Onsite</th>
                        <th class="px-4 py-2.5 text-right">Offsite</th>
                        <th class="px-4 py-2.5 text-right">Total Jam</th>
                        <th class="px-4 py-2.5 text-right">Logbook</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($baris as $b)
                        <tr wire:key="lap-{{ $b['user']->id }}" class="hover:bg-slate-50">
                            <td class="px-4 py-2.5 font-medium text-slate-700">{{ $b['user']->name }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-500">{{ $b['user']->employment_type?->label() }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-green-700">{{ $b['hadir'] }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-amber-700">{{ $b['terlambat'] }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums {{ $b['alpha'] ? 'font-semibold text-red-700' : 'text-slate-400' }}">{{ $b['alpha'] }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-slate-600">{{ $b['onsite'] }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-slate-600">{{ $b['offsite'] }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums font-medium text-brand-700">{{ intdiv($b['menit'], 60) }}j {{ $b['menit'] % 60 }}m</td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-slate-600">{{ $b['logbook'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-[11px] text-slate-400">
        Freelance: jam masuk tidak ditegakkan (hanya dicatat). Kolom "Alpha" diisi otomatis oleh <code>kehadiran:rekap</code> (cron).
    </p>
</div>
