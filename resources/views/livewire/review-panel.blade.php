<div>
    @if ($terbuka && $task)
        @php
            $sayaArtis = $task->artists->contains(auth()->id());
            $st = $task->status;
        @endphp
        <div class="fixed inset-0 z-40 bg-black/40" wire:click="tutup"></div>

        <aside class="fixed inset-y-0 right-0 z-50 flex w-full max-w-2xl flex-col bg-slate-50 shadow-2xl">
            {{-- Header --}}
            <div class="flex items-start justify-between border-b border-slate-200 bg-white px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">
                        {{ $task->shot->shot_code }}
                        <span class="text-slate-300">/</span>
                        {{ $task->tahap?->name }}
                    </h2>
                    <p class="text-xs text-slate-500">{{ $task->shot->adegan->scene_name }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <span class="inline-flex items-center rounded px-2 py-0.5 text-[11px] font-medium {{ $st->color() }}">{{ $st->label() }}</span>
                        <span class="inline-flex items-center rounded px-2 py-0.5 text-[10px] {{ $task->revision_status->color() }}">{{ $task->revision_status->label() }}</span>
                        @if ($retake > 0)
                            <span class="inline-flex items-center rounded bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700" title="Jumlah pengembalian revisi">↻ Retake {{ $retake }}</span>
                        @endif
                        <span class="text-[11px] text-slate-400">· Anda: <span class="font-medium text-slate-600">{{ $peninjau?->name ?? 'Tamu' }}</span></span>
                    </div>
                </div>
                <button wire:click="tutup" class="text-slate-400 hover:text-slate-700">&times;</button>
            </div>

            <div class="flex-1 space-y-4 overflow-y-auto p-4">

                {{-- ============ MEDIA (bersama) ============ --}}
                <section class="rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Pratinjau Deliverable</h3>
                    <x-media-embed :url="$task->preview_url" />
                    @if ($task->preview_url)
                        <p class="mt-1 text-[11px] text-slate-400">Tautan Google Drive — pastikan berbagi <span class="font-medium">"Anyone with the link"</span>.</p>
                    @else
                        <p class="text-xs text-slate-400">Belum ada tautan kiriman.</p>
                    @endif

                    {{-- Referensi Shotlist (metadata dari pra-produksi) --}}
                    @if ($bisaKelola && $metaKolom->isNotEmpty())
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <div class="mb-1.5 flex items-center justify-between">
                                <h4 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Referensi Shotlist</h4>
                                <button wire:click="simpanMeta" class="rounded bg-slate-700 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-slate-800">Simpan Referensi</button>
                            </div>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($metaKolom as $mk)
                                    <div>
                                        <label class="mb-0.5 block text-[10px] font-medium text-slate-500">{{ $mk->label }}</label>
                                        @if ($mk->tipe === 'select')
                                            <select wire:model="metaEdit.{{ $mk->key }}" class="block w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                                <option value="">—</option>
                                                @foreach ($mk->opsi ?? [] as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
                                            </select>
                                        @else
                                            <input type="{{ $mk->tipe === 'number' ? 'number' : 'text' }}" wire:model="metaEdit.{{ $mk->key }}" class="block w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif (! empty($task->shot->meta))
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <h4 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Referensi Shotlist</h4>
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-1 text-xs sm:grid-cols-2">
                                @foreach ($task->shot->meta as $mk => $mv)
                                    @if ($mv !== null && $mv !== '')
                                        <div class="flex gap-1">
                                            <dt class="shrink-0 font-medium text-slate-500">{{ $shotlistLabel[$mk] ?? \Illuminate\Support\Str::headline($mk) }}:</dt>
                                            <dd class="text-slate-700">{{ $mv }}</dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    {{-- Kesiapan aset (breakdown shot) --}}
                    @if ($task->shot->aset->isNotEmpty())
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <h4 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Aset dipakai shot ini</h4>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($task->shot->aset as $a)
                                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] {{ $a->status === \App\Enums\TaskStatus::APPROVED ? 'border-green-200 bg-green-50 text-green-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                        {{ $a->status === \App\Enums\TaskStatus::APPROVED ? '✓' : '•' }} {{ $a->name }} <span class="opacity-60">({{ $a->status->label() }})</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                {{-- ============ PANEL ARTIS — PENGISIAN PROGRESS ============ --}}
                <section class="overflow-hidden rounded-xl border border-blue-200 bg-white">
                    <header class="flex items-center gap-2 border-b border-blue-100 bg-blue-50 px-4 py-2.5">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-600 text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        </span>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-blue-900">Panel Artis — Pengisian Progress</h3>
                            <p class="text-[11px] text-blue-700/70">Diisi oleh artis yang ditugaskan.</p>
                        </div>
                        @if ($sayaArtis)
                            <span class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold text-white">Bagian Anda</span>
                        @endif
                    </header>

                    <div class="space-y-3 p-4">
                        {{-- Tautan kiriman + catatan versi --}}
                        @if ($bisaKerja || $bisaKelola)
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Tautan video / file kiriman (Drive)</label>
                                <div class="flex items-end gap-2">
                                    <input type="url" wire:model="previewUrl" placeholder="https://drive.google.com/..."
                                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <button wire:click="simpanPreview" class="shrink-0 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Simpan Kiriman</button>
                                </div>
                                @error('previewUrl') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                <input type="text" wire:model="versiCatatan" maxlength="500" placeholder="Catatan versi (opsional, mis. 'v2 perbaikan timing')"
                                       class="mt-2 w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        @else
                            <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">Hanya artis yang ditugaskan (atau pengelola) yang dapat mengisi kiriman.</p>
                        @endif

                        {{-- Aksi progres artis --}}
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($st === \App\Enums\TaskStatus::NOT_STARTED)
                                @if ($bisaKerja && ! $prereqOk)
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-500">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        🔒 Tahap “{{ $prereqNama }}” harus disetujui dulu
                                    </span>
                                @elseif ($bisaKerja)
                                    <button wire:click="ubahStatus('IN_PROGRESS')" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">Mulai Kerjakan</button>
                                @else
                                    <span class="text-xs text-slate-400">Menunggu penugasan / publish episode.</span>
                                @endif
                            @elseif ($st === \App\Enums\TaskStatus::IN_PROGRESS)
                                @if ($bisaKerja)
                                    <button x-on:click="$confirm(@js('Ajukan pekerjaan ini untuk direview?'), { confirmText: 'Ajukan', icon: 'info' }).then(ok => ok && $wire.ubahStatus('REVIEW'))"
                                            class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">Ajukan Review</button>
                                @else
                                    <span class="text-xs text-slate-400">Sedang dikerjakan artis.</span>
                                @endif
                            @elseif ($st === \App\Enums\TaskStatus::REVIEW)
                                <span class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700">Menunggu peninjauan →</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">✓ Disetujui</span>
                            @endif
                        </div>
                    </div>
                </section>

                {{-- ============ PANEL PENINJAU — PENINJAUAN ============ --}}
                <section class="overflow-hidden rounded-xl border border-green-200 bg-white">
                    <header class="flex items-center gap-2 border-b border-green-100 bg-green-50 px-4 py-2.5">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-green-600 text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-green-900">Panel Peninjau — Peninjauan</h3>
                            <p class="text-[11px] text-green-700/70">Setup & keputusan oleh Supervisor / Team Lead.</p>
                        </div>
                        @if ($bisaReview)
                            <span class="rounded-full bg-green-600 px-2 py-0.5 text-[10px] font-semibold text-white">Bagian Anda</span>
                        @endif
                    </header>

                    <div class="space-y-4 p-4">
                        {{-- Penugasan artis --}}
                        <div>
                            <h4 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Artis Ditugaskan</h4>
                            @if ($bisaKelola)
                                <div class="max-h-40 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2">
                                    @foreach ($daftarArtis as $a)
                                        <label class="flex cursor-pointer items-center gap-2 rounded px-1.5 py-1 text-xs text-slate-700 hover:bg-slate-50">
                                            <input type="checkbox" wire:model="artisIds" value="{{ $a->id }}" class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                                            <span class="font-medium">{{ $a->name }}</span>
                                            <span class="text-slate-400">· {{ $a->role ?? '—' }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <button wire:click="simpanArtis" class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Simpan Penugasan
                                </button>
                                @error('artisIds.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @elseif ($task->artists->isNotEmpty())
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($task->artists as $a)
                                        <span class="inline-flex items-center rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-medium text-brand-700">{{ $a->name }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-slate-400">Belum ada artis ditugaskan.</p>
                            @endif
                        </div>

                        {{-- Jadwal --}}
                        <div>
                            <h4 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Jadwal</h4>
                            @if ($bisaKelola)
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="mb-1 block text-[11px] text-slate-500">Tanggal mulai</label>
                                        <input type="date" wire:model="startDate" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[11px] text-slate-500">Deadline</label>
                                        <input type="date" wire:model="deadline" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                                        @error('deadline') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[11px] text-slate-500">Estimasi (hari)</label>
                                        <input type="number" min="0" wire:model="estimasiHari" placeholder="mis. 3" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                                        @error('estimasiHari') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <button wire:click="simpanPreview" class="mt-2 rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Simpan Jadwal</button>
                            @else
                                <p class="text-xs text-slate-500">
                                    Mulai: <span class="font-medium">{{ $task->start_date?->locale('id')->isoFormat('D MMM Y') ?? '—' }}</span> ·
                                    Deadline: <span class="font-medium">{{ $task->deadline?->locale('id')->isoFormat('D MMM Y') ?? '—' }}</span> ·
                                    Estimasi: <span class="font-medium">{{ $task->estimasi_hari ? $task->estimasi_hari.' hari' : '—' }}</span>
                                </p>
                            @endif
                        </div>

                        {{-- Deskripsi shot --}}
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <h4 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Deskripsi Shot</h4>
                                @if ($bisaKelola && $aiAktif)
                                    <button wire:click="isiDeskripsiShotAi" wire:loading.attr="disabled" wire:target="isiDeskripsiShotAi"
                                            class="inline-flex items-center gap-1 rounded bg-gold-100 px-2 py-1 text-[11px] font-semibold text-gold-700 hover:bg-gold-200 disabled:opacity-50">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                        <span wire:loading.remove wire:target="isiDeskripsiShotAi">Bantu tulis (AI)</span>
                                        <span wire:loading wire:target="isiDeskripsiShotAi">Menulis…</span>
                                    </button>
                                @endif
                            </div>
                            @if ($bisaKelola)
                                <textarea wire:model="deskripsiShot" rows="2" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Aksi/komposisi pada shot ini…"></textarea>
                                @error('deskripsiShot') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                <button wire:click="simpanDeskripsiShot" class="mt-1 rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Simpan Deskripsi</button>
                            @elseif ($task->shot?->description)
                                <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">{{ $task->shot->description }}</p>
                            @else
                                <p class="text-xs text-slate-400">Belum ada deskripsi.</p>
                            @endif
                        </div>

                        {{-- Keputusan review --}}
                        <div class="border-t border-slate-100 pt-3">
                            <h4 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Keputusan Review</h4>
                            @error('workflow')
                                <div class="mb-2 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $message }}</div>
                            @enderror
                            @if ($bisaReview && $st === \App\Enums\TaskStatus::REVIEW)
                                <textarea wire:model="catatan" rows="2" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Catatan revisi / persetujuan…"></textarea>
                                @error('catatan') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <button wire:click="ubahStatus('APPROVED')" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">Setujui</button>
                                    <button wire:click="mintaRevisi" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">Minta Revisi</button>
                                </div>
                            @elseif ($st === \App\Enums\TaskStatus::REVIEW)
                                <p class="text-xs text-amber-600">Menunggu keputusan Supervisor / Team Lead.</p>
                            @elseif ($st === \App\Enums\TaskStatus::APPROVED)
                                <p class="text-xs font-medium text-green-700">✓ Sudah disetujui.</p>
                            @else
                                <p class="text-xs text-slate-400">Belum ada yang perlu ditinjau (artis belum mengajukan).</p>
                            @endif
                        </div>
                    </div>
                </section>

                {{-- ============ CATATAN REVIEW (per-frame) ============ --}}
                <section class="rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Catatan Review (per-frame)</h3>

                    @if ($bisaReview)
                        <div class="mb-3 rounded-lg border border-slate-200 bg-slate-50 p-2.5">
                            <div class="flex gap-2">
                                <input type="number" min="0" wire:model="noteFrameStart" placeholder="Frame awal" class="w-28 rounded-lg border-slate-300 text-xs shadow-sm focus:border-green-500 focus:ring-green-500">
                                <input type="number" min="0" wire:model="noteFrameEnd" placeholder="Frame akhir" class="w-28 rounded-lg border-slate-300 text-xs shadow-sm focus:border-green-500 focus:ring-green-500">
                                @error('noteFrameEnd') <span class="self-center text-[11px] text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <textarea wire:model="noteBody" rows="2" placeholder="mis. 'frame 112: arc tangan pop, retime 88–96'" class="mt-2 w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-green-500 focus:ring-green-500 @error('noteBody') border-red-400 @enderror"></textarea>
                            @error('noteBody') <p class="text-[11px] text-red-600">{{ $message }}</p> @enderror
                            <button wire:click="tambahCatatanReview" class="mt-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">Tambah Catatan</button>
                        </div>
                    @endif

                    <ul class="space-y-1.5">
                        @forelse ($task->catatanReview as $c)
                            <li wire:key="crev-{{ $c->id }}" class="flex items-start gap-2 rounded-lg border px-3 py-2 text-xs {{ $c->selesai() ? 'border-slate-100 bg-slate-50 opacity-70' : 'border-amber-200 bg-amber-50' }}">
                                <button wire:click="toggleCatatanReview({{ $c->id }})" class="mt-0.5 shrink-0" title="{{ $c->selesai() ? 'Buka lagi' : 'Tandai selesai' }}">
                                    <span class="flex h-4 w-4 items-center justify-center rounded border {{ $c->selesai() ? 'border-green-500 bg-green-500 text-white' : 'border-slate-300' }}">
                                        @if ($c->selesai())<svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>@endif
                                    </span>
                                </button>
                                <div class="flex-1">
                                    @if ($c->frame_start !== null)
                                        <span class="mr-1 rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-semibold text-white">f{{ $c->frame_start }}{{ $c->frame_end !== null && $c->frame_end !== $c->frame_start ? '–'.$c->frame_end : '' }}</span>
                                    @endif
                                    <span class="{{ $c->selesai() ? 'text-slate-500 line-through' : 'text-slate-700' }}">{{ $c->body }}</span>
                                    <div class="mt-0.5 text-[10px] text-slate-400">{{ $c->author?->name ?? 'Sistem' }} · {{ $c->created_at?->diffForHumans() }}</div>
                                </div>
                            </li>
                        @empty
                            <li class="text-xs text-slate-400">Belum ada catatan review.</li>
                        @endforelse
                    </ul>
                </section>

                {{-- ============ RIWAYAT & DISKUSI (bersama) ============ --}}
                <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-4">
                    {{-- Riwayat versi --}}
                    @if ($task->versi->isNotEmpty())
                        <div>
                            <h3 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Riwayat Versi ({{ $task->versi->count() }})</h3>
                            <ul class="space-y-1">
                                @foreach ($task->versi as $v)
                                    <li wire:key="versi-{{ $v->id }}" class="flex items-center gap-2 rounded border border-slate-100 bg-slate-50 px-2.5 py-1.5 text-xs">
                                        <span class="shrink-0 rounded bg-brand-100 px-1.5 py-0.5 font-semibold text-brand-700">v{{ $v->version }}</span>
                                        <a href="{{ $v->url }}" target="_blank" rel="noopener" class="truncate text-brand-600 hover:underline">{{ $v->catatan ?: $v->url }}</a>
                                        <span class="ml-auto shrink-0 text-[10px] text-slate-400">{{ $v->author?->name ?? 'Sistem' }} · {{ $v->created_at?->locale('id')->diffForHumans() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Riwayat revisi --}}
                    <div>
                        <h3 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Riwayat Revisi ({{ $task->revisi->count() }})</h3>
                        <ul class="space-y-2">
                            @forelse ($task->revisi as $r)
                                <li wire:key="rev-{{ $r->id }}" class="rounded border border-slate-100 bg-slate-50 px-3 py-2">
                                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                                        <span>{{ $r->author?->name ?? 'Sistem' }} · {{ $r->kind }}</span>
                                        <span>{{ $r->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if ($r->note)<p class="mt-1 text-xs text-slate-700">{{ $r->note }}</p>@endif
                                    @if ($r->status_from !== $r->status_to)<p class="mt-1 text-[11px] text-slate-400">{{ $r->status_from }} → {{ $r->status_to }}</p>@endif
                                </li>
                            @empty
                                <li class="text-xs text-slate-400">Belum ada riwayat revisi.</li>
                            @endforelse
                        </ul>
                    </div>

                    {{-- Diskusi --}}
                    <div class="border-t border-slate-100 pt-3">
                        <livewire:komentar :subjek-type="App\Models\TugasShot::class" :subjek-id="$task->id" :key="'kom-shot-'.$task->id" />
                    </div>
                </section>
            </div>
        </aside>
    @endif
</div>
