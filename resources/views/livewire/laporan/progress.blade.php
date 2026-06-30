<div class="mx-auto max-w-5xl px-4 py-6">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Laporan Progress</h1>
        <p class="mt-1 text-sm text-slate-500">Persentase penyelesaian tiap episode per fase (tugas yang disetujui ÷ total tugas).</p>
    </div>

    {{-- Ringkasan: overdue & beban per artis --}}
    <div class="mb-5 grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-800">
                <span class="inline-flex h-5 items-center rounded-full bg-red-100 px-2 text-[11px] font-bold text-red-600">{{ $overdue->count() }}</span>
                Tugas lewat deadline
            </h2>
            @forelse ($overdue->take(8) as $o)
                <div wire:key="od-{{ $loop->index }}" class="flex items-center justify-between gap-2 border-t border-slate-50 py-1.5 text-xs">
                    <div class="min-w-0">
                        <div class="truncate font-medium text-slate-700">{{ $o['label'] }}</div>
                        <div class="truncate text-[11px] text-slate-400">{{ $o['episode'] }} · {{ $o['siapa'] }}</div>
                    </div>
                    <span class="shrink-0 rounded bg-red-50 px-1.5 py-0.5 text-[11px] font-semibold text-red-600">{{ $o['telat'] }} hari</span>
                </div>
            @empty
                <p class="py-3 text-xs text-slate-400">🎉 Tidak ada tugas yang lewat deadline.</p>
            @endforelse
            @if ($overdue->count() > 8)<p class="mt-2 text-[11px] text-slate-400">…dan {{ $overdue->count() - 8 }} lainnya.</p>@endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-2 text-sm font-semibold text-slate-800">Beban kerja per artis <span class="text-xs font-normal text-slate-400">(tugas aktif)</span></h2>
            @php $maks = $beban->max() ?: 1; @endphp
            @forelse ($beban->take(10) as $nama => $jml)
                <div wire:key="bb-{{ $loop->index }}" class="py-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="truncate text-slate-600">{{ $nama }}</span>
                        <span class="tabular-nums font-medium text-slate-500">{{ $jml }}</span>
                    </div>
                    <div class="mt-0.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full {{ $jml >= 6 ? 'bg-red-400' : 'bg-brand-500' }}" style="width: {{ round($jml / $maks * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="py-3 text-xs text-slate-400">Belum ada tugas aktif yang ditugaskan.</p>
            @endforelse
        </div>
    </div>

    @forelse ($data as $row)
        @php $ep = $row['ep']; @endphp
        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" wire:key="ep-{{ $ep->id }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold text-slate-800">{{ $ep->name }}</h2>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $ep->status->color() }}">{{ $ep->status->label() }}</span>
                    </div>
                    <p class="text-xs text-slate-400">{{ $ep->klien?->name ?? 'Tanpa klien' }} · {{ (int) $ep->total_durasi }}s total durasi</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-brand-700">{{ $row['persen'] }}%</div>
                    <div class="text-[11px] text-slate-400">selesai</div>
                </div>
            </div>

            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-brand-600" style="width: {{ $row['persen'] }}%"></div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach ($row['fase'] as $f)
                    @php $p = $f['total'] > 0 ? (int) round($f['done'] / $f['total'] * 100) : 0; @endphp
                    <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-600">{{ $f['label'] }}</span>
                            <span class="tabular-nums text-slate-500">{{ $f['done'] }}/{{ $f['total'] }}</span>
                        </div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full {{ $p === 100 ? 'bg-green-500' : 'bg-gold-400' }}" style="width: {{ $p }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            Belum ada episode untuk dilaporkan.
        </div>
    @endforelse
</div>
