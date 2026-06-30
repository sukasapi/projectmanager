<div wire:poll.15s class="space-y-5">
    {{-- Header halaman --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Shot Pipeline Matrix</h1>
            <p class="text-sm text-slate-500">
                @if ($proyek)
                    Episode: <span class="font-medium text-slate-700">{{ $proyek->name }}</span> · lacak status tiap tahap, durasi otomatis.
                @else
                    Pilih episode yang akan dikerjakan.
                @endif
            </p>
        </div>
        @if ($proyek)
            <button wire:click="gantiEpisode"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                Ganti Episode
            </button>
        @endif
    </div>

    @if (! $proyek)
        {{-- Pemilih episode: kartu portofolio. Non-supervisor hanya melihat episode miliknya. --}}
        @if ($episodes->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                Belum ada episode yang tersedia untuk Anda. Hubungi supervisor untuk penugasan.
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($episodes as $ep)
                    <button type="button" wire:click="pilihEpisode({{ $ep->id }})" wire:key="ep-card-{{ $ep->id }}"
                            class="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold text-slate-800 transition group-hover:text-brand-700">{{ $ep->name }}</h3>
                            <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $ep->status->color() }}">{{ $ep->status->label() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $ep->klien?->name ?? 'Tanpa klien' }}</p>
                        @if ($ep->description)
                            <p class="mt-2 line-clamp-2 text-xs text-slate-500">{{ $ep->description }}</p>
                        @endif
                        <div class="mt-4 flex items-center gap-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                            <span>{{ $ep->adegan_count }} adegan</span>
                            <span class="tabular-nums">{{ (int) $ep->total_durasi }}s</span>
                            <span class="ml-auto font-medium text-brand-600 transition group-hover:translate-x-0.5">Buka →</span>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    @else

    @if ($proyek->isClosed())
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm text-slate-500">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            Episode <span class="font-semibold">selesai (closed)</span> — read-only. Buka kembali dari menu <span class="font-medium">Episode</span> untuk mengedit.
        </div>
    @endif

    @if ($dapatKelola)
        {{-- Card aksi setup (Supervisor/Team Lead) --}}
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <span class="text-sm font-medium text-slate-500">Setup produksi:</span>
            <button wire:click="bukaSceneForm"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gold-400 px-3.5 py-2 text-sm font-semibold text-brand-900 shadow-sm transition hover:bg-gold-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Tambah Scene
            </button>
            <button wire:click="bukaShotForm" @disabled($proyek->adegan->isEmpty())
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Tambah Shot
            </button>
            <button wire:click="bukaBulk" @disabled($proyek->adegan->isEmpty())
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" /></svg>
                Assign Massal
            </button>
            @if ($proyek->adegan->isEmpty())<span class="text-xs text-slate-400">Buat scene dulu sebelum menambah shot.</span>@endif
        </div>
    @endif

    {{-- Modal assign massal --}}
    @if ($showBulk)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="tutupBulk"></div>
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Assign artis ke banyak shot</h3>
                    <button wire:click="tutupBulk" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Tahap</label>
                            <select wire:model="bulkTahapId" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @foreach ($tahapKolom as $th)
                                    <option value="{{ $th->id }}">{{ $th->name }}</option>
                                @endforeach
                            </select>
                            @error('bulkTahapId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Lingkup</label>
                            <select wire:model="bulkSceneId" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Semua scene</option>
                                @foreach ($daftarAdegan as $sc)
                                    <option value="{{ $sc->id }}">{{ $sc->scene_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Artis</label>
                        <div class="max-h-44 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2">
                            @foreach ($daftarArtis as $a)
                                <label class="flex cursor-pointer items-center gap-2 rounded px-1.5 py-1 text-sm text-slate-700 hover:bg-slate-50">
                                    <input type="checkbox" wire:model="bulkArtisIds" value="{{ $a->id }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    <span class="font-medium">{{ $a->name }}</span>
                                    <span class="text-slate-400">· {{ $a->role ?? '—' }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('bulkArtisIds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-4 text-sm text-slate-600">
                        <label class="flex items-center gap-1.5"><input type="radio" wire:model="bulkMode" value="tambah" class="text-brand-600 focus:ring-brand-500"> Tambahkan</label>
                        <label class="flex items-center gap-1.5"><input type="radio" wire:model="bulkMode" value="ganti" class="text-brand-600 focus:ring-brand-500"> Ganti (timpa)</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button wire:click="tutupBulk" class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:bg-slate-100">Batal</button>
                        <button wire:click="assignMassal" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Terapkan</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($proyek->adegan->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            Belum ada scene/shot pada episode ini.
            @if ($dapatKelola) Klik <span class="font-medium text-slate-700">Tambah Scene</span> untuk memulai. @endif
        </div>
    @else
        {{-- Legenda --}}
        <div class="mb-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
            <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded ring-2 ring-brand-400"></span> Sel bertanda <span class="rounded bg-brand-600 px-1 text-[10px] font-bold text-white">Anda</span> = tugas Anda</span>
            <span>Tombol: <span class="font-semibold text-blue-600">Mulai</span> → <span class="font-semibold text-amber-600">Ajukan Review</span> → <span class="font-semibold text-green-600">Tinjau</span> (oleh supervisor/team lead)</span>
        </div>

        {{-- Kartu matriks --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/70 text-[11px] uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3 text-left font-semibold">Shot</th>
                            <th class="px-4 py-3 text-right font-semibold">Durasi</th>
                            @foreach ($tahapKolom as $tahap)
                                <th class="border-l border-slate-100/70 px-4 py-3 text-left font-semibold">{{ $tahap->name }}</th>
                            @endforeach
                            <th class="border-l border-slate-100/70 px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>

                    @foreach ($proyek->adegan as $adegan)
                        <tbody class="divide-y divide-slate-100">
                            {{-- Judul scene --}}
                            <tr class="bg-brand-50/60">
                                <td colspan="2" class="px-4 py-2 text-sm font-semibold text-brand-900">
                                    {{ $adegan->scene_name }}
                                </td>
                                <td colspan="{{ count($tahapKolom) }}" class="px-4 py-2 text-xs text-brand-600">
                                    {{ $adegan->shot->count() }} shot
                                </td>
                                <td class="bg-brand-50/60"></td>
                            </tr>

                            @forelse ($adegan->shot as $shot)
                                <tr wire:key="shot-{{ $shot->id }}" class="group transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-4 py-2 font-mono font-medium text-slate-800">{{ $shot->shot_code }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right tabular-nums text-slate-500">{{ $shot->duration_seconds }}s</td>

                                    @foreach ($tahapKolom as $tahap)
                                        @php
                                            $tugas = $shot->tugasShot->first(fn ($t) => $t->tahap_id === $tahap->id);
                                            $milik = $tugas && $tugas->artists->contains($uid);
                                            $bisaBuka = $tugas && ($kelolaEpisode || $milik);
                                            $st = $tugas?->status;
                                            $btn = 'w-full rounded-md px-2 py-1 text-[11px] font-semibold transition';
                                            // Dependensi pipeline: tahap ini butuh prasyarat (tahap sebelumnya) APPROVED dulu.
                                            $prereqOk = true;
                                            $prereqNama = null;
                                            if ($tugas && $tahap->requires_tahap_id) {
                                                $pre = $shot->tugasShot->first(fn ($t) => $t->tahap_id === $tahap->requires_tahap_id);
                                                $prereqOk = $pre && $pre->status === \App\Enums\TaskStatus::APPROVED;
                                                $prereqNama = optional($tahapKolom->firstWhere('id', $tahap->requires_tahap_id))->name ?? 'tahap sebelumnya';
                                            }
                                        @endphp
                                        <td class="border-l border-slate-100/60 px-2 py-2 align-top">
                                            @if (! $tugas)
                                                <span class="text-slate-300">—</span>
                                            @else
                                                <div @class([
                                                    'rounded-lg p-2',
                                                    'ring-2 ring-brand-400 bg-brand-50/60' => $milik,
                                                    'bg-slate-50/40' => ! $milik && $bisaBuka,
                                                ])>
                                                    <div class="mb-1 flex flex-wrap items-center gap-1">
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $st->color() }}">{{ $st->label() }}</span>
                                                        @if ($milik)
                                                            <span class="inline-flex items-center rounded-full bg-brand-600 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">Anda</span>
                                                        @endif
                                                    </div>

                                                    @if ($tugas->artists->isNotEmpty())
                                                        <p class="mb-1.5 truncate text-[11px] {{ $milik ? 'font-semibold text-brand-700' : 'text-slate-500' }}" title="{{ $tugas->artists->pluck('name')->join(', ') }}">{{ $tugas->artists->pluck('name')->join(', ') }}</p>
                                                    @else
                                                        <p class="mb-1.5 text-[11px] italic text-slate-300">belum ada artis</p>
                                                    @endif

                                                    @if ($milik && ! $prereqOk && $st === \App\Enums\TaskStatus::NOT_STARTED)
                                                        <span class="flex w-full items-center justify-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-400" title="Selesaikan & setujui {{ $prereqNama }} dulu">
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                            Menunggu {{ $prereqNama }}
                                                        </span>
                                                    @elseif ($milik && $published && $st === \App\Enums\TaskStatus::NOT_STARTED)
                                                        <button wire:click="mulaiShot({{ $tugas->id }})" class="{{ $btn }} bg-blue-600 text-white hover:bg-blue-700">▶ Mulai</button>
                                                    @elseif ($milik && $st === \App\Enums\TaskStatus::IN_PROGRESS)
                                                        <button wire:click="review({{ $tugas->id }})" class="{{ $btn }} bg-amber-500 text-white hover:bg-amber-600">⤴ Ajukan Review</button>
                                                    @elseif ($bisaReviewEpisode && $st === \App\Enums\TaskStatus::REVIEW)
                                                        <button wire:click="review({{ $tugas->id }})" class="{{ $btn }} bg-green-600 text-white hover:bg-green-700">Tinjau</button>
                                                    @elseif ($milik && $st === \App\Enums\TaskStatus::REVIEW)
                                                        <button wire:click="review({{ $tugas->id }})" title="Buka untuk lihat / perbarui kiriman selagi menunggu ditinjau"
                                                                class="{{ $btn }} flex items-center justify-center gap-1 border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100">
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                            Menunggu review
                                                        </button>
                                                    @elseif ($st === \App\Enums\TaskStatus::APPROVED)
                                                        @if ($bisaBuka)
                                                            <button wire:click="review({{ $tugas->id }})" class="{{ $btn }} border border-green-200 text-green-700 hover:bg-green-50">✓ Lihat</button>
                                                        @else
                                                            <span class="block text-center text-[11px] font-semibold text-green-600">✓ Disetujui</span>
                                                        @endif
                                                    @elseif ($bisaBuka)
                                                        <button wire:click="review({{ $tugas->id }})" class="{{ $btn }} border border-slate-200 text-slate-600 hover:bg-slate-50">Buka</button>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="border-l border-slate-100/60 px-3 py-2.5 text-center">
                                        @if ($dapatKelola)
                                            <button type="button"
                                                    x-on:click="$confirm(@js('Hapus shot '.$shot->shot_code.' beserta seluruh sub-task-nya?'), { danger: true }).then(ok => ok && $wire.deleteShot({{ $shot->id }}))"
                                                    title="Hapus shot"
                                                    class="inline-flex items-center justify-center rounded-md bg-slate-100 p-1.5 text-red-500 transition hover:bg-red-100 hover:text-red-700">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ 3 + count($tahapKolom) }}" class="px-4 py-3 text-slate-400">Belum ada shot.</td></tr>
                            @endforelse

                            {{-- Subtotal durasi scene --}}
                            <tr class="bg-slate-50/80 font-medium text-slate-600">
                                <td class="px-4 py-2 text-right">Total {{ $adegan->scene_name }}</td>
                                <td class="px-4 py-2 text-right tabular-nums font-semibold text-brand-700">{{ $adegan->total_duration }}s</td>
                                <td colspan="{{ count($tahapKolom) + 1 }}"></td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        </div>

        <p class="flex items-center gap-1.5 text-xs text-slate-400">
            <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-green-500"></span>
            Klik sel status untuk membuka panel review &amp; revisi · menyegar otomatis tiap 15 detik.
        </p>
    @endif
    @endif

    {{-- Modal tambah scene --}}
    @if ($showSceneForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="tutupSceneForm"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Tambah Scene</h2>
                    <button wire:click="tutupSceneForm" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <form wire:submit="addScene" class="space-y-4 px-5 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nama scene</label>
                        <input type="text" wire:model="newSceneName" placeholder="Scene 03" autofocus
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('newSceneName') border-red-400 @enderror">
                        @error('newSceneName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="tutupSceneForm" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan Scene</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal tambah shot (kode otomatis) --}}
    @if ($showShotForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="tutupShotForm"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Tambah Shot</h2>
                    <button wire:click="tutupShotForm" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <form wire:submit="addShot" class="space-y-4 px-5 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Scene</label>
                        <select wire:model.live="newSceneId" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('newSceneId') border-red-400 @enderror">
                            <option value="">— pilih scene —</option>
                            @foreach ($daftarAdegan as $a)
                                <option value="{{ $a->id }}">{{ $a->scene_name }}</option>
                            @endforeach
                        </select>
                        @error('newSceneId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Kode Shot <span class="text-slate-400">(otomatis)</span></label>
                            <input type="text" value="{{ $newShotCode }}" readonly
                                   class="block w-full rounded-lg border-slate-200 bg-slate-50 font-mono text-sm text-slate-600 shadow-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Durasi (detik)</label>
                            <input type="number" min="0" wire:model="newDurationSeconds"
                                   class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('newDurationSeconds') border-red-400 @enderror">
                            @error('newDurationSeconds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="tutupShotForm" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan Shot</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Panel review --}}
    <livewire:review-panel />
</div>
