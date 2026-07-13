<div class="space-y-5">
    @if (! $proyekId)
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Shotlist</h1>
            <p class="text-sm text-slate-500">Pilih episode untuk menyusun shotlist (isi manual / impor CSV) lalu generate ke Produksi.</p>
        </div>
        @if ($episodes->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">Belum ada episode.</div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($episodes as $ep)
                    <button wire:click="pilihEpisode({{ $ep->id }})" wire:key="ep-{{ $ep->id }}" class="group rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-brand-400 hover:shadow">
                        <div class="font-semibold text-slate-800 group-hover:text-brand-700">{{ $ep->name }}</div>
                    </button>
                @endforeach
            </div>
        @endif
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button wire:click="gantiEpisode" class="text-slate-400 hover:text-brand-700" title="Ganti episode">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Shotlist — {{ $proyek->name }}</h1>
                    <p class="text-sm text-slate-500">{{ $rows->count() }} baris · {{ $belumDigenerate }} belum di-generate ke Produksi.@if ($bisaKelola) <span class="text-slate-400">· klik 2× pada sel untuk edit cepat</span>@endif</p>
                </div>
            </div>
            @if ($bisaKelola)
                <div class="flex flex-wrap items-center gap-2">
                    <button wire:click="tambah" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Tambah Baris
                    </button>
                    @if ($aiAktif)
                        <button wire:click="bukaFormAi" class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-violet-700" title="Buat shotlist dari skenario dengan bantuan AI">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
                            Buat dengan AI
                        </button>
                    @endif
                    @php
                        $genPesan = $shotlistDisetujui
                            ? 'Generate '.$belumDigenerate.' baris shotlist menjadi shot di Produksi?'
                            : 'Tahap Shotlist belum disetujui ('.($shotlistStatusLabel ?? 'belum ada').'). Tetap generate '.$belumDigenerate.' baris ke Produksi?';
                    @endphp
                    @if ($belumDigenerate === 0)
                        <span title="{{ $rows->isEmpty() ? 'Isi shotlist dulu (manual / impor CSV / AI)' : 'Semua baris sudah di-generate — lihat hasil di Shot Matrix' }}">
                            <button type="button" disabled
                                    class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3.5 py-2 text-sm font-semibold text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                Generate ke Produksi
                            </button>
                        </span>
                    @else
                        <button x-on:click="$confirm(@js($genPesan), { confirmText: 'Generate', icon: @js($shotlistDisetujui ? 'info' : 'warning') }).then(ok => ok && $wire.generate())"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold text-white shadow-sm {{ $shotlistDisetujui ? 'bg-brand-700 hover:bg-brand-800' : 'bg-amber-600 hover:bg-amber-700' }}"
                                title="{{ $shotlistDisetujui ? 'Buat '.$belumDigenerate.' baris menjadi shot di Produksi' : 'Tahap Shotlist belum disetujui — masih bisa generate, tapi sebaiknya disetujui dulu' }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                            Generate ke Produksi ({{ $belumDigenerate }})
                        </button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Banner: seluruh shotlist sudah jadi shot di Produksi --}}
        @if ($rows->isNotEmpty() && $belumDigenerate === 0)
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3">
                <svg class="h-5 w-5 shrink-0 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="min-w-0 flex-1 text-sm text-green-800">
                    Seluruh <span class="font-semibold">{{ $rows->count() }} baris</span> shotlist sudah di-generate menjadi shot di Produksi. Data kolom lain (VO, Visual, dll) tersimpan sebagai metadata shot — terlihat saat membuka panel review shot.
                </p>
                <a href="{{ route('shot-matrix') }}" wire:navigate
                   class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-green-700">
                    Buka Shot Matrix
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </a>
            </div>
        @endif

        {{-- Kartu ringkas satu baris: Skenario Episode & Impor CSV (klik → modal) --}}
        @if ($bisaIsiSkenario || $bisaKelola)
            <div class="grid gap-3 sm:grid-cols-2">
                @if ($bisaIsiSkenario)
                    <button type="button" wire:click="bukaSkenario" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-brand-400 hover:shadow">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-800 group-hover:text-brand-700">Skenario Episode</span>
                            <span class="block truncate text-[11px] text-slate-400">{{ trim($skenario) === '' ? 'Belum diisi — klik untuk menulis naskah.' : Str::limit(trim($skenario), 70) }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium {{ trim($skenario) === '' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">{{ trim($skenario) === '' ? 'belum diisi' : 'terisi' }}</span>
                    </button>
                @endif
                @if ($bisaKelola)
                    <button type="button" wire:click="bukaImpor" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-brand-400 hover:shadow">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15M9 12l3 3m0 0l3-3m-3 3V2.25" /></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-800 group-hover:text-brand-700">Impor CSV</span>
                            <span class="block truncate text-[11px] text-slate-400">Unggah CSV — hasil menggantikan shotlist yang ada.</span>
                        </span>
                        @if ($shotlistStatusLabel)
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium {{ $shotlistDisetujui ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $shotlistStatusLabel }}</span>
                        @endif
                    </button>
                @endif
            </div>
        @endif

        {{-- Modal Skenario Episode --}}
        @if ($showSkenarioModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-brand-950/60" wire:click="tutupSkenario"></div>
                <div class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Skenario Episode</h2>
                            <p class="text-[11px] text-slate-400">Skenario dipakai sebagai sumber saat membuat shotlist dengan AI.</p>
                        </div>
                        <button wire:click="tutupSkenario" class="text-slate-400 hover:text-slate-700">&times;</button>
                    </div>
                    <div class="flex-1 space-y-2 overflow-y-auto px-5 py-4">
                        <textarea wire:model="skenario" rows="14" placeholder="Tempel / tulis skenario (naskah) episode di sini…" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        @error('skenario') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
                        <button type="button" wire:click="tutupSkenario" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                        <button wire:click="simpanSkenario" wire:loading.attr="disabled" wire:target="simpanSkenario" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-50">
                            <span wire:loading.remove wire:target="simpanSkenario">Simpan Skenario</span>
                            <span wire:loading wire:target="simpanSkenario">Menyimpan…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal Impor CSV --}}
        @if ($showImporModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-brand-950/60" wire:click="tutupImpor"></div>
                <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Impor CSV</h2>
                            <p class="text-[11px] text-slate-400">Hasil impor <span class="font-medium text-rose-500">menggantikan shotlist yang ada</span> (ada pratinjau dulu).</p>
                        </div>
                        <button wire:click="tutupImpor" class="text-slate-400 hover:text-slate-700">&times;</button>
                    </div>
                    <div class="space-y-4 px-5 py-5">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Berkas CSV</label>
                            <input type="file" wire:model="csv" accept=".csv,text/csv" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                            @error('csv') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-[11px] text-slate-400">Header CSV dicocokkan otomatis ke nama kolom (koma / titik-koma). Kolom yang tak cocok diabaikan.</p>
                        </div>
                        @if ($shotlistStatusLabel)
                            <p class="text-[11px] {{ $shotlistDisetujui ? 'text-green-600' : 'text-amber-600' }}">
                                Tahap Shotlist (Pra-Produksi): <span class="font-semibold">{{ $shotlistStatusLabel }}</span>{{ $shotlistDisetujui ? ' — siap di-generate.' : ' — sebaiknya disetujui dulu sebelum generate.' }}
                            </p>
                        @endif
                        <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-4">
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" x-on:click="open = !open" x-on:click.outside="open = false" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50" title="Unduh template CSV berisi header kolom studio">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Template
                                    <svg class="h-3.5 w-3.5 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak class="absolute bottom-full left-0 z-20 mb-1 w-52 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                    <button type="button" x-on:click="open = false; $wire.unduhTemplate('koma')" class="block w-full px-3 py-2 text-left text-sm text-slate-600 hover:bg-slate-50">
                                        Pemisah koma <span class="font-mono text-slate-400">(,)</span>
                                    </button>
                                    <button type="button" x-on:click="open = false; $wire.unduhTemplate('titik-koma')" class="block w-full px-3 py-2 text-left text-sm text-slate-600 hover:bg-slate-50">
                                        Pemisah titik-koma <span class="font-mono text-slate-400">(;)</span> <span class="text-[10px] text-slate-400">— Excel ID</span>
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="tutupImpor" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                                <button x-data
                                        x-on:click="
                                            window.Swal.fire({
                                                title: 'Mengimpor CSV…',
                                                html: `Membaca & mencocokkan kolom shotlist.
                                                    <div style='margin-top:16px;height:8px;width:100%;overflow:hidden;border-radius:9999px;background:#e2e8f0'>
                                                        <div style='height:100%;width:35%;border-radius:9999px;background:#2b4f62;animation:slImporBar 1.1s ease-in-out infinite'></div>
                                                    </div>`,
                                                allowOutsideClick: false,
                                                allowEscapeKey: false,
                                                showConfirmButton: false,
                                            });
                                            {{-- Tutup via promise, bukan event: tombol ini ikut terhapus saat modal impor ditutup sehingga listener event tidak sempat jalan. --}}
                                            $wire.importCsv().finally(() => window.Swal && window.Swal.close());
                                        "
                                        wire:loading.attr="disabled" wire:target="importCsv,csv"
                                        class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="importCsv">Impor</span>
                                    <span wire:loading wire:target="importCsv">Mengimpor…</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <style>
                        @keyframes slImporBar {
                            0% { margin-left: -35%; }
                            100% { margin-left: 100%; }
                        }
                    </style>
                </div>
            </div>
        @endif

        {{-- Toggle tampilan: tree (Scene → VO → Shot) atau tabel penuh --}}
        <div class="flex items-center justify-between">
            <div class="inline-flex rounded-lg border border-slate-300 bg-white p-0.5 shadow-sm">
                <button wire:click="gantiTampilan('tree')" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition {{ $tampilan === 'tree' ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" /></svg>
                    Tree
                </button>
                <button wire:click="gantiTampilan('tabel')" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition {{ $tampilan === 'tabel' ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M3 6h18M3 18h18" /></svg>
                    Tabel
                </button>
            </div>
            @if ($tampilan === 'tree')
                <p class="text-[11px] text-slate-400">Scene → VO → Shot · VO ditulis sekali dan berlaku untuk shot di bawahnya.</p>
            @endif
        </div>

        @if ($tampilan === 'tree')
        {{-- Tampilan tree: Scene → grup VO → shot --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="max-h-[70vh] overflow-auto">
                @php
                    $kolomLain = $kolom->reject(fn ($k) => in_array($k->key, array_filter($kunciTree), true));
                @endphp
                @forelse ($pohon as $iScene => $scene)
                    <div wire:key="tree-scene-{{ $iScene }}" x-data="{ open: false }" class="border-b border-slate-200 last:border-b-0">
                        {{-- Node scene --}}
                        <button type="button" x-on:click="open = !open" class="flex w-full items-center gap-2 bg-slate-50 px-4 py-2.5 text-left hover:bg-slate-100">
                            <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="open && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            <span class="font-semibold text-slate-800">{{ $scene['scene'] }}</span>
                            <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold text-brand-700">{{ collect($scene['grup'])->sum(fn ($g) => count($g['rows'])) }} shot</span>
                            @if ($scene['detik'] > 0)
                                <span class="ml-auto text-xs tabular-nums text-slate-500">{{ intdiv($scene['detik'], 60) }}:{{ str_pad((string) ($scene['detik'] % 60), 2, '0', STR_PAD_LEFT) }} <span class="text-slate-400">({{ $scene['detik'] }} dtk)</span></span>
                            @endif
                        </button>

                        <div x-show="open" x-cloak>
                            @foreach ($scene['grup'] as $iGrup => $grup)
                                <div wire:key="tree-grup-{{ $iScene }}-{{ $iGrup }}" class="border-t border-slate-100">
                                    {{-- Node VO (sekali per grup, berlaku untuk semua shot di bawahnya) --}}
                                    @if (trim($grup['vo']) !== '')
                                        <div class="flex items-start gap-2 bg-violet-50/60 py-2 pl-10 pr-4">
                                            <span class="mt-0.5 shrink-0 rounded bg-violet-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-violet-700">VO</span>
                                            <p class="whitespace-pre-line text-[13px] italic leading-relaxed text-violet-900">{{ $grup['vo'] }}</p>
                                            <span class="ml-auto shrink-0 self-center text-[10px] text-violet-400">{{ count($grup['rows']) }} shot</span>
                                        </div>
                                    @endif

                                    {{-- Shot di bawah VO --}}
                                    @foreach ($grup['rows'] as $row)
                                        <div wire:key="tree-row-{{ $row->id }}" class="group flex items-start gap-3 border-t border-slate-100 py-2.5 pl-14 pr-4 first:border-t-0 hover:bg-brand-50/40">
                                            <div class="flex w-24 shrink-0 flex-col gap-1">
                                                <span class="font-mono text-xs font-semibold text-slate-700">{{ $kunciTree['code'] && trim((string) ($row->data[$kunciTree['code']] ?? '')) !== '' ? $row->data[$kunciTree['code']] : '#'.$row->urutan }}</span>
                                                @if ($kunciTree['dur'] && (int) ($row->data[$kunciTree['dur']] ?? 0) > 0)
                                                    <span class="text-[11px] tabular-nums text-slate-400">{{ (int) $row->data[$kunciTree['dur']] }} dtk</span>
                                                @endif
                                                @if ($row->shot_id)
                                                    <span class="inline-flex w-fit items-center rounded-full bg-green-100 px-1.5 py-0.5 text-[9px] font-medium text-green-700">✓ dibuat</span>
                                                @else
                                                    <span class="inline-flex w-fit items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-medium text-amber-700">belum</span>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1 space-y-1">
                                                @foreach ($kolomLain as $k)
                                                    @php $nilai = trim((string) ($row->data[$k->key] ?? '')); @endphp
                                                    @if ($nilai !== '')
                                                        <div wire:key="tree-sel-{{ $row->id }}-{{ $k->key }}"
                                                             @if ($bisaKelola) wire:dblclick="mulaiEditSel({{ $row->id }}, '{{ $k->key }}')" title="Klik 2× untuk edit" @endif
                                                             class="text-[13px] leading-relaxed text-slate-700 {{ $bisaKelola ? 'cursor-cell rounded px-1 -mx-1 hover:bg-brand-50' : '' }}">
                                                            <span class="mr-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $k->label }}:</span><span class="whitespace-pre-line">{{ $nilai }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                            @if ($bisaKelola)
                                                <div class="flex shrink-0 items-center gap-1 opacity-0 transition group-hover:opacity-100">
                                                    <button wire:click="edit({{ $row->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 hover:bg-brand-100 hover:text-brand-700" title="Edit">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                    </button>
                                                    <button x-on:click="$confirm(@js('Hapus baris shotlist ini?'), { danger: true }).then(ok => ok && $wire.hapus({{ $row->id }}))" class="inline-flex rounded-md bg-slate-100 p-1.5 text-red-500 hover:bg-red-100 hover:text-red-700" title="Hapus">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-xs text-slate-400">Belum ada baris shotlist. Tambah manual atau impor CSV.</div>
                @endforelse
            </div>

            {{-- Rekap: total scene, total shot, total durasi --}}
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500">Total scene:</span>
                    <span class="font-semibold tabular-nums text-slate-800">{{ $rekap['scene'] }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500">Total shot:</span>
                    <span class="font-semibold tabular-nums text-slate-800">{{ $rekap['shot'] }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500">Durasi:</span>
                    <span class="font-semibold tabular-nums text-slate-800">{{ number_format($rekap['detik'], 0, ',', '.') }} detik</span>
                    <span class="text-slate-400">({{ $rekap['menit'] }} menit)</span>
                </div>
            </div>
        </div>
        @else
        {{-- Tabel shotlist --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="max-h-[70vh] overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 z-10">
                        <tr class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-2.5 text-left font-semibold">#</th>
                            @foreach ($kolom as $k)
                                <th class="whitespace-nowrap px-3 py-2.5 text-left font-semibold">
                                    {{ $k->label }}
                                    @if ($k->peran)<span class="ml-1 rounded bg-brand-100 px-1 py-0.5 text-[9px] font-semibold text-brand-700">{{ $k->peran->value }}</span>@endif
                                </th>
                            @endforeach
                            <th class="px-3 py-2.5 text-center font-semibold">Produksi</th>
                            @if ($bisaKelola)<th class="px-3 py-2.5 text-center font-semibold">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr wire:key="sl-{{ $row->id }}" class="align-top transition odd:bg-white even:bg-slate-50/60 hover:bg-brand-50/40">
                                <td class="px-3 py-2.5 tabular-nums text-slate-400">{{ $row->urutan }}</td>
                                @foreach ($kolom as $k)
                                    <td wire:key="sel-{{ $row->id }}-{{ $k->key }}"
                                        @if ($bisaKelola) wire:dblclick="mulaiEditSel({{ $row->id }}, '{{ $k->key }}')" title="Klik 2× untuk edit" @endif
                                        class="max-w-[16rem] px-3 py-2.5 text-slate-700 {{ $bisaKelola ? 'cursor-cell hover:bg-brand-50' : '' }}">
                                        <div class="line-clamp-3 whitespace-pre-line">{{ trim((string) ($row->data[$k->key] ?? '')) !== '' ? $row->data[$k->key] : '—' }}</div>
                                    </td>
                                @endforeach
                                <td class="px-3 py-2.5 text-center">
                                    @if ($row->shot_id)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-700">✓ dibuat</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700">belum</span>
                                    @endif
                                </td>
                                @if ($bisaKelola)
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="edit({{ $row->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 hover:bg-brand-100 hover:text-brand-700" title="Edit">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button x-on:click="$confirm(@js('Hapus baris shotlist ini?'), { danger: true }).then(ok => ok && $wire.hapus({{ $row->id }}))" class="inline-flex rounded-md bg-slate-100 p-1.5 text-red-500 hover:bg-red-100 hover:text-red-700" title="Hapus">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ 3 + $kolom->count() }}" class="px-4 py-6 text-center text-xs text-slate-400">Belum ada baris shotlist. Tambah manual atau impor CSV.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Rekap: total scene, total shot, total durasi --}}
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500">Total scene:</span>
                    <span class="font-semibold tabular-nums text-slate-800">{{ $rekap['scene'] }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500">Total shot:</span>
                    <span class="font-semibold tabular-nums text-slate-800">{{ $rekap['shot'] }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500">Durasi:</span>
                    <span class="font-semibold tabular-nums text-slate-800">{{ number_format($rekap['detik'], 0, ',', '.') }} detik</span>
                    <span class="text-slate-400">({{ $rekap['menit'] }} menit)</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Modal edit satu sel (klik 2× pada tabel) — input mengikuti tipe kolom master --}}
        @php $kolomSel = $editCellId ? $kolom->firstWhere('key', $editCellKey) : null; @endphp
        @if ($editCellId && $kolomSel)
            @php $barisSel = $rows->firstWhere('id', $editCellId); @endphp
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-brand-950/60" wire:click="batalEditSel"></div>
                <div class="relative w-full max-w-sm rounded-xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">{{ $kolomSel->label }}</h2>
                            <p class="text-[11px] text-slate-400">Baris #{{ $barisSel?->urutan }}@if ($kolomSel->peran) · peran: {{ $kolomSel->peran->value }}@endif</p>
                        </div>
                        <button wire:click="batalEditSel" class="text-slate-400 hover:text-slate-700">&times;</button>
                    </div>
                    <form wire:submit="simpanSel" class="space-y-4 px-5 py-5">
                        @if ($kolomSel->tipe === 'select')
                            <select wire:model="editCellValue" wire:keydown.escape="batalEditSel" x-init="$el.focus()"
                                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">—</option>
                                @foreach ($kolomSel->opsi ?? [] as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
                            </select>
                        @elseif ($kolomSel->tipe === 'number')
                            <input type="number" wire:model="editCellValue" wire:keydown.escape="batalEditSel"
                                   x-init="$el.focus(); $el.select && $el.select()"
                                   class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @elseif (mb_strlen((string) ($barisSel?->data[$editCellKey] ?? '')) > 20)
                            {{-- Isi sel panjang (>20 karakter) → textarea agar nyaman disunting --}}
                            <textarea wire:model="editCellValue" rows="5" wire:keydown.escape="batalEditSel"
                                      x-init="$el.focus()"
                                      class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        @else
                            <input type="text" wire:model="editCellValue" wire:keydown.escape="batalEditSel"
                                   x-init="$el.focus(); $el.select && $el.select()"
                                   class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @endif
                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                            <button type="button" wire:click="batalEditSel" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Modal pratinjau hasil impor CSV — konfirmasi sebelum dijadikan shotlist --}}
        @if ($showPratinjauImpor)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-brand-950/60" wire:click="batalImpor"></div>
                <div class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Hasil Impor CSV</h2>
                            <p class="text-[11px] text-slate-400">{{ count($pratinjauImpor) }} baris terbaca — periksa dulu, lalu klik "Jadikan Shotlist" untuk menyimpan.</p>
                        </div>
                        <button wire:click="batalImpor" class="text-slate-400 hover:text-slate-700">&times;</button>
                    </div>
                    <div class="flex-1 overflow-auto">
                        <table class="min-w-full text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                                    <th class="px-3 py-2 text-left font-semibold">#</th>
                                    @foreach ($kolom as $k)
                                        <th class="whitespace-nowrap px-3 py-2 text-left font-semibold">{{ $k->label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($pratinjauImpor as $i => $data)
                                    <tr wire:key="pi-{{ $i }}" class="align-top odd:bg-white even:bg-slate-50/60">
                                        <td class="px-3 py-2 tabular-nums text-slate-400">{{ $i + 1 }}</td>
                                        @foreach ($kolom as $k)
                                            <td class="max-w-[14rem] px-3 py-2 text-slate-700">
                                                <div class="line-clamp-3 whitespace-pre-line">{{ trim((string) ($data[$k->key] ?? '')) !== '' ? $data[$k->key] : '—' }}</div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
                        <p class="text-[11px] text-slate-400"><span class="font-medium text-rose-500">Shotlist lama akan dikosongkan</span> dan diganti baris ini — belum ada yang tersimpan sampai dikonfirmasi.</p>
                        <div class="flex gap-2">
                            <button wire:click="batalImpor" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                            <button wire:click="konfirmasiImpor" wire:loading.attr="disabled" wire:target="konfirmasiImpor" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                <span wire:loading.remove wire:target="konfirmasiImpor">Jadikan Shotlist ({{ count($pratinjauImpor) }})</span>
                                <span wire:loading wire:target="konfirmasiImpor">Menyimpan…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal generate AI --}}
        @if ($showAiForm)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-brand-950/60" wire:click="tutupFormAi"></div>
                <div class="relative w-full max-w-md rounded-xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-semibold text-slate-900">Buat Shotlist dengan AI</h2>
                        <button wire:click="tutupFormAi" class="text-slate-400 hover:text-slate-700">&times;</button>
                    </div>
                    <div class="space-y-4 px-5 py-5">
                        <p class="text-sm text-slate-500">AI akan memecah <span class="font-medium text-slate-700">skenario episode</span> menjadi baris shotlist sesuai kolom studio (scene, shot, visual, deskripsi, dll). Hasil <span class="font-medium text-rose-600">menggantikan seluruh shotlist yang ada</span> (dikosongkan otomatis) — periksa & sunting dulu sebelum Generate ke Produksi.</p>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Perkiraan durasi episode (menit) <span class="text-slate-400">(opsional)</span></label>
                            <input type="number" min="1" max="240" wire:model="estimasiMenit" placeholder="kosongkan agar AI menentukan sendiri" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('estimasiMenit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-[11px] text-slate-400">Bila diisi, total durasi shot ≈ durasi ini. Bila kosong, AI menentukan durasi tiap shot dari isi skenario.</p>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <label class="block text-sm font-medium text-slate-700">Instruksi tambahan untuk AI</label>
                                <button type="button" wire:click="resetInstruksiAi" class="text-[11px] font-medium text-brand-600 hover:underline" title="Kembalikan ke instruksi bawaan studio">Kembalikan default</button>
                            </div>
                            <textarea wire:model="instruksiAi" rows="10" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('instruksiAi') border-red-400 @enderror"></textarea>
                            @error('instruksiAi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-[11px] text-slate-400">Instruksi bawaan (Storyboard Director, Realistic Cinematic) sudah terisi — sunting sesuai kebutuhan episode; AI wajib mengikutinya saat mengisi shotlist.</p>
                        </div>
                        @error('ai') <p class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                            <button type="button" wire:click="tutupFormAi" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                            <button wire:click="generateAi" wire:loading.attr="disabled" wire:target="generateAi" class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-50">
                                <svg wire:loading wire:target="generateAi" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                <span wire:loading.remove wire:target="generateAi">Generate</span>
                                <span wire:loading wire:target="generateAi">Menghasilkan… (±1–2 menit)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal baris --}}
        @if ($showForm)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-brand-950/60" wire:click="cancel"></div>
                <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-semibold text-slate-900">{{ $editingId ? 'Edit Baris' : 'Tambah Baris' }}</h2>
                        <button wire:click="cancel" class="text-slate-400 hover:text-slate-700">&times;</button>
                    </div>
                    <form wire:submit="save" class="space-y-3 px-5 py-5">
                        @foreach ($kolom as $k)
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">{{ $k->label }}@if ($k->peran)<span class="ml-1 text-[10px] text-brand-600">({{ $k->peran->value }})</span>@endif</label>
                                @if ($k->tipe === 'select')
                                    <select wire:model="baris.{{ $k->key }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        <option value="">—</option>
                                        @foreach ($k->opsi ?? [] as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
                                    </select>
                                @elseif ($k->tipe === 'number')
                                    <input type="number" wire:model="baris.{{ $k->key }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @else
                                    <input type="text" wire:model="baris.{{ $k->key }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @endif
                            </div>
                        @endforeach
                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                            <button type="button" wire:click="cancel" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">{{ $editingId ? 'Simpan' : 'Tambah' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif
</div>
