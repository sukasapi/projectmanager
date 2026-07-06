<div class="mx-auto max-w-5xl px-4 py-6">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Laporan Progress</h1>
            <p class="mt-1 text-sm text-slate-500">Penyelesaian per episode & fase, progres berbobot durasi, beban kru, dan laju penyelesaian.</p>
        </div>
        <button wire:click="unduhCsv" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Unduh CSV
        </button>
    </div>

    {{-- Velocity / burndown --}}
    <div class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-800">Laju penyelesaian <span class="text-xs font-normal text-slate-400">(approval per minggu, 8 minggu)</span></h2>
            <div class="text-xs text-slate-500">
                Rata-rata <span class="font-semibold text-brand-700">{{ $velocity['avg'] }}/minggu</span> ·
                Sisa <span class="font-semibold text-slate-700">{{ $velocity['sisa'] }} tugas</span>
                @if ($velocity['proyeksiTanggal']) · Proyeksi selesai <span class="font-semibold text-green-700">± {{ $velocity['proyeksiTanggal'] }}</span>@endif
            </div>
        </div>
        @php $maxV = max(1, collect($velocity['buckets'])->max('count')); @endphp
        <div class="flex items-end gap-1.5" style="height: 64px;">
            @foreach ($velocity['buckets'] as $b)
                <div class="flex flex-1 flex-col items-center justify-end" title="{{ $b['label'] }}: {{ $b['count'] }} approval">
                    <div class="w-full rounded-t bg-brand-500" style="height: {{ round($b['count'] / $maxV * 100) }}%"></div>
                    <span class="mt-1 text-[9px] text-slate-400">{{ $b['label'] }}</span>
                </div>
            @endforeach
        </div>
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
            <h2 class="mb-2 text-sm font-semibold text-slate-800">Beban kerja per artis <span class="text-xs font-normal text-slate-400">(alokasi vs kapasitas/minggu)</span></h2>
            @forelse ($beban->take(10) as $b)
                @php $warna = $b['persen'] > 100 ? 'bg-red-500' : ($b['persen'] >= 80 ? 'bg-amber-400' : 'bg-brand-500'); @endphp
                <div wire:key="bb-{{ $loop->index }}" class="py-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="truncate text-slate-600">{{ $b['nama'] }} <span class="text-slate-400">· {{ $b['tugas'] }} tugas</span></span>
                        <span class="tabular-nums font-medium {{ $b['persen'] > 100 ? 'text-red-600' : 'text-slate-500' }}">{{ $b['hari'] }}/{{ $b['kapasitas'] }} hari · {{ $b['persen'] }}%</span>
                    </div>
                    <div class="mt-0.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full {{ $warna }}" style="width: {{ min(100, $b['persen']) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="py-3 text-xs text-slate-400">Belum ada tugas aktif yang ditugaskan.</p>
            @endforelse
            <p class="mt-2 text-[10px] text-slate-400">Merah = alokasi &gt; 100% (overload — bersifat informasional, tidak memblokir penugasan).</p>
        </div>
    </div>

    {{-- Funnel bottleneck per tahap Produksi --}}
    @if ($funnel->isNotEmpty())
        <div class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-slate-800">Bottleneck per tahap <span class="text-xs font-normal text-slate-400">(shot-task aktif)</span></h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($funnel as $nama => $f)
                    <div wire:key="fn-{{ $loop->index }}" class="rounded-lg border border-slate-100 bg-slate-50/60 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">{{ $nama }}</span>
                            <span class="tabular-nums text-sm font-bold text-brand-700">{{ $f['total'] }}</span>
                        </div>
                        <div class="mt-2 flex h-2 overflow-hidden rounded-full bg-slate-200">
                            @if ($f['not_started']) <div class="bg-slate-400" style="width: {{ round($f['not_started'] / $f['total'] * 100) }}%" title="Belum mulai"></div>@endif
                            @if ($f['in_progress']) <div class="bg-blue-500" style="width: {{ round($f['in_progress'] / $f['total'] * 100) }}%" title="Dikerjakan"></div>@endif
                            @if ($f['review']) <div class="bg-amber-500" style="width: {{ round($f['review'] / $f['total'] * 100) }}%" title="Menunggu review"></div>@endif
                        </div>
                        <div class="mt-1.5 flex gap-3 text-[10px] text-slate-500">
                            <span>◻ {{ $f['not_started'] }} belum</span>
                            <span class="text-blue-600">● {{ $f['in_progress'] }} kerja</span>
                            <span class="text-amber-600">● {{ $f['review'] }} review</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
                    <div class="text-[11px] text-slate-400">selesai (per tugas)</div>
                    @if ($row['persenDurasi'] !== null)
                        <div class="mt-0.5 text-sm font-semibold text-slate-600">{{ $row['persenDurasi'] }}% <span class="text-[10px] font-normal text-slate-400">berbobot durasi</span></div>
                    @endif
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
