<div class="px-4 py-6">
    @if (! $proyek)
        <div class="mb-5">
            <h1 class="text-xl font-bold text-slate-900">Timeline / Jadwal</h1>
            <p class="mt-1 text-sm text-slate-500">Pilih episode untuk melihat lini masa tugas (Pra · Produksi · Pasca).</p>
        </div>

        @if ($episodes->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                Tidak ada episode yang dapat Anda lihat. Timeline hanya untuk Super Admin, Supervisor, atau Team Lead episode.
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($episodes as $ep)
                    <button type="button" wire:click="pilihEpisode({{ $ep->id }})" wire:key="ep-{{ $ep->id }}"
                            class="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold text-slate-800 transition group-hover:text-brand-700">{{ $ep->name }}</h3>
                            <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $ep->status->color() }}">{{ $ep->status->label() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $ep->klien?->name ?? 'Tanpa klien' }}</p>
                        <span class="mt-4 text-xs font-medium text-brand-600">Lihat timeline →</span>
                    </button>
                @endforeach
            </div>
        @endif
    @else
        @php
            $warna = [
                'NOT_STARTED' => 'bg-slate-300',
                'IN_PROGRESS' => 'bg-blue-500',
                'REVIEW' => 'bg-amber-500',
                'APPROVED' => 'bg-green-500',
            ];
        @endphp

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <button wire:click="gantiEpisode" class="mb-1 text-xs font-medium text-brand-600 hover:underline">← Ganti episode</button>
                <h1 class="text-xl font-bold text-slate-900">Timeline · {{ $proyek->name }}</h1>
            </div>
            @if ($rangeStart)
                <span class="text-xs text-slate-500">{{ $rangeStart->locale('id')->isoFormat('D MMM Y') }} — {{ $rangeEnd->locale('id')->isoFormat('D MMM Y') }}</span>
            @endif
        </div>

        @if ($grup->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                Belum ada tugas berjadwal. Isi tanggal mulai &amp; deadline pada tugas (panel review shot / tracker Pra-Pasca) agar muncul di sini.
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="min-w-[680px]">
                    {{-- Sumbu tanggal --}}
                    <div class="flex">
                        <div class="w-52 shrink-0"></div>
                        <div class="relative h-5 flex-1">
                            @foreach ($ticks as $t)
                                <div class="absolute -translate-x-1/2 text-[10px] text-slate-400" style="left: {{ $t['left'] }}%">{{ $t['tanggal']->locale('id')->isoFormat('D MMM') }}</div>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative">
                        {{-- Garis bantu vertikal melintasi semua baris --}}
                        <div class="pointer-events-none absolute inset-0 ml-52">
                            @foreach ($ticks as $t)
                                <div class="absolute bottom-0 top-0 w-px bg-slate-100" style="left: {{ $t['left'] }}%"></div>
                            @endforeach
                        </div>

                        @foreach ($grup as $faseLabel => $bars)
                            <div class="mt-3 mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $faseLabel }}</div>
                            @foreach ($bars as $b)
                                <div class="flex items-center py-0.5" wire:key="bar-{{ $faseLabel }}-{{ $loop->index }}">
                                    <div class="w-52 shrink-0 truncate pr-3 text-xs font-medium text-slate-700" title="{{ $b['label'] }}">{{ $b['label'] }}</div>
                                    <div class="relative h-6 flex-1">
                                        <div class="absolute top-0.5 flex h-5 items-center rounded {{ $warna[$b['status']->value] ?? 'bg-slate-300' }} px-1.5 text-[10px] font-medium text-white shadow-sm"
                                             style="left: {{ $b['left'] }}%; width: {{ $b['width'] }}%; min-width: 6px"
                                             title="{{ $b['label'] }} — {{ $b['mulai']->locale('id')->isoFormat('D MMM') }} s/d {{ $b['selesai']->locale('id')->isoFormat('D MMM Y') }} ({{ $b['status']->label() }})">
                                            <span class="truncate">{{ $b['selesai']->locale('id')->isoFormat('D MMM') }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
                <span class="font-medium">Status:</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded bg-slate-300"></span> Belum mulai</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded bg-blue-500"></span> Dikerjakan</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded bg-amber-500"></span> Review</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded bg-green-500"></span> Disetujui</span>
                @if ($tanpaJadwal > 0)
                    <span class="ml-auto text-slate-400">{{ $tanpaJadwal }} tugas tanpa tanggal (tidak ditampilkan)</span>
                @endif
            </div>
        @endif
    @endif
</div>
