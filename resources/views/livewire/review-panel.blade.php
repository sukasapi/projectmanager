<div>
    @if ($terbuka && $task)
        <div class="fixed inset-0 z-40 bg-black/40" wire:click="tutup"></div>

        <aside class="fixed inset-y-0 right-0 z-50 flex w-full max-w-3xl flex-col bg-white shadow-2xl">
            {{-- Header --}}
            <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">
                        {{ $task->shot->shot_code }}
                        <span class="text-gray-400">/</span>
                        {{ $task->tahap?->name }}
                    </h2>
                    <p class="text-xs text-gray-500">{{ $task->shot->adegan->scene_name }}</p>
                </div>
                <button wire:click="tutup" class="text-gray-400 hover:text-gray-700">&times;</button>
            </div>

            <div class="grid flex-1 grid-cols-1 gap-0 overflow-y-auto md:grid-cols-2">
                {{-- Kolom kiri: preview video --}}
                <div class="border-r border-gray-200 p-5">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Preview</h3>

                    <x-media-embed :url="$task->preview_url" />
                    @if ($task->preview_url)
                        <p wire:key="hint-{{ $task->id }}" class="mt-1 text-[11px] text-slate-400">Tautan Google Drive ditampilkan via pratinjau. Pastikan berbagi disetel <span class="font-medium">"Anyone with the link"</span>.</p>
                    @endif

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Tanggal mulai</label>
                            <input type="date" wire:model="startDate" class="w-full rounded border-gray-300 text-sm shadow-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Deadline</label>
                            <input type="date" wire:model="deadline" class="w-full rounded border-gray-300 text-sm shadow-sm">
                            @error('deadline') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mt-3 flex items-end gap-2">
                        <div class="flex-1">
                            <label class="mb-1 block text-xs font-medium text-gray-600">Tautan video / file (link Drive)</label>
                            <input type="url" wire:model="previewUrl" placeholder="https://drive.google.com/..."
                                   class="w-full rounded border-gray-300 text-sm shadow-sm">
                            @error('previewUrl') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <button wire:click="simpanPreview"
                                class="rounded bg-gray-800 px-3 py-2 text-xs font-medium text-white hover:bg-gray-900">
                            Simpan
                        </button>
                    </div>
                    <input type="text" wire:model="versiCatatan" maxlength="500"
                           placeholder="Catatan versi (opsional, mis. 'v2 perbaikan timing')"
                           class="mt-2 w-full rounded border-gray-300 text-xs shadow-sm">

                    {{-- Riwayat versi deliverable --}}
                    @if ($task->versi->isNotEmpty())
                        <div class="mt-3">
                            <h3 class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Riwayat Versi ({{ $task->versi->count() }})</h3>
                            <ul class="space-y-1">
                                @foreach ($task->versi as $v)
                                    <li wire:key="versi-{{ $v->id }}" class="flex items-center gap-2 rounded border border-gray-100 bg-gray-50 px-2.5 py-1.5 text-xs">
                                        <span class="shrink-0 rounded bg-brand-100 px-1.5 py-0.5 font-semibold text-brand-700">v{{ $v->version }}</span>
                                        <a href="{{ $v->url }}" target="_blank" rel="noopener" class="truncate text-brand-600 hover:underline">{{ $v->catatan ?: $v->url }}</a>
                                        <span class="ml-auto shrink-0 text-[10px] text-gray-400">{{ $v->author?->name ?? 'Sistem' }} · {{ $v->created_at?->locale('id')->diffForHumans() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Deskripsi shot (+ bantu AI) --}}
                    <div class="mt-4">
                        <div class="mb-1 flex items-center justify-between">
                            <label class="text-xs font-medium text-gray-600">Deskripsi shot</label>
                            @if ($bisaKelola && $aiAktif)
                                <button wire:click="isiDeskripsiShotAi" wire:loading.attr="disabled" wire:target="isiDeskripsiShotAi"
                                        class="inline-flex items-center gap-1 rounded bg-gold-100 px-2 py-1 text-[11px] font-semibold text-gold-700 transition hover:bg-gold-200 disabled:opacity-50">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                    <span wire:loading.remove wire:target="isiDeskripsiShotAi">Bantu tulis (AI)</span>
                                    <span wire:loading wire:target="isiDeskripsiShotAi">Menulis…</span>
                                </button>
                            @endif
                        </div>
                        @if ($bisaKelola)
                            <textarea wire:model="deskripsiShot" rows="3"
                                      class="w-full rounded border-gray-300 text-sm shadow-sm"
                                      placeholder="Aksi/komposisi pada shot ini…"></textarea>
                            @error('deskripsiShot') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            <button wire:click="simpanDeskripsiShot"
                                    class="mt-1 rounded bg-gray-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-900">
                                Simpan Deskripsi
                            </button>
                        @elseif ($task->shot?->description)
                            <p class="rounded bg-slate-50 px-3 py-2 text-sm text-slate-600">{{ $task->shot->description }}</p>
                        @else
                            <p class="text-xs text-slate-400">Belum ada deskripsi.</p>
                        @endif
                    </div>
                </div>

                {{-- Kolom kanan: status, aksi, revisi --}}
                <div class="space-y-4 p-5">
                    {{-- Status & peninjau --}}
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center rounded px-2 py-1 text-xs font-medium {{ $task->status->color() }}">
                            {{ $task->status->label() }}
                        </span>
                        <span class="inline-flex items-center rounded px-2 py-0.5 text-[11px] {{ $task->revision_status->color() }}">
                            {{ $task->revision_status->label() }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 rounded border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        <span class="font-medium text-slate-500">Peninjau:</span>
                        <span class="font-semibold text-slate-800">{{ $peninjau?->name ?? 'Tamu' }}</span>
                        @if ($peninjau?->employment_type)
                            <span class="rounded bg-slate-200 px-1.5 py-0.5 text-[10px]">{{ $peninjau->employment_type->label() }}</span>
                        @endif
                    </div>

                    {{-- Penugasan artis (Super Admin / Supervisor / Team Lead) --}}
                    <div>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Artis Ditugaskan</h3>
                        @if ($bisaKelola)
                            <div class="max-h-44 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2">
                                @foreach ($daftarArtis as $a)
                                    <label class="flex cursor-pointer items-center gap-2 rounded px-1.5 py-1 text-xs text-slate-700 hover:bg-slate-50">
                                        <input type="checkbox" wire:model="artisIds" value="{{ $a->id }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        <span class="font-medium">{{ $a->name }}</span>
                                        <span class="text-slate-400">· {{ $a->role ?? '—' }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <button wire:click="simpanArtis"
                                    class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-800">
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

                    @error('workflow')
                        <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                            {{ $message }}
                        </div>
                    @enderror

                    {{-- Catatan (dipakai saat approve / minta revisi) --}}
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Catatan</label>
                        <textarea wire:model="catatan" rows="2"
                                  class="w-full rounded border-gray-300 text-sm shadow-sm"
                                  placeholder="Catatan revisi / persetujuan..."></textarea>
                        @error('catatan') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    {{-- Aksi transisi sesuai status & peran --}}
                    <div class="flex flex-wrap gap-2">
                        @if ($task->status === \App\Enums\TaskStatus::NOT_STARTED)
                            @if ($bisaKerja)
                                <button wire:click="ubahStatus('IN_PROGRESS')"
                                        class="rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                                    Mulai Kerjakan
                                </button>
                            @else
                                <span class="text-xs text-slate-400">Menunggu penugasan / publish episode.</span>
                            @endif
                        @elseif ($task->status === \App\Enums\TaskStatus::IN_PROGRESS)
                            @if ($bisaKerja)
                                <button x-on:click="$confirm(@js('Ajukan pekerjaan ini untuk direview?'), { confirmText: 'Ajukan', icon: 'info' }).then(ok => ok && $wire.ubahStatus('REVIEW'))"
                                        class="rounded bg-amber-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-600">
                                    Ajukan Review
                                </button>
                            @endif
                        @elseif ($task->status === \App\Enums\TaskStatus::REVIEW)
                            @if ($bisaReview)
                                <button wire:click="ubahStatus('APPROVED')"
                                        class="rounded bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">
                                    Setujui
                                </button>
                                <button wire:click="mintaRevisi"
                                        class="rounded bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                                    Minta Revisi
                                </button>
                            @else
                                <span class="text-xs text-amber-600">Menunggu review supervisor/team lead.</span>
                            @endif
                        @else
                            <span class="text-xs font-medium text-green-700">✓ Tahap disetujui</span>
                        @endif
                    </div>

                    {{-- Riwayat revisi (append-only) --}}
                    <div>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Riwayat Revisi ({{ $task->revisi->count() }})
                        </h3>
                        <ul class="space-y-2">
                            @forelse ($task->revisi as $r)
                                <li wire:key="rev-{{ $r->id }}" class="rounded border border-gray-100 bg-gray-50 px-3 py-2">
                                    <div class="flex items-center justify-between text-[11px] text-gray-500">
                                        <span>{{ $r->author?->name ?? 'Sistem' }} · {{ $r->kind }}</span>
                                        <span>{{ $r->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if ($r->note)
                                        <p class="mt-1 text-xs text-gray-700">{{ $r->note }}</p>
                                    @endif
                                    @if ($r->status_from !== $r->status_to)
                                        <p class="mt-1 text-[11px] text-gray-400">
                                            {{ $r->status_from }} → {{ $r->status_to }}
                                        </p>
                                    @endif
                                </li>
                            @empty
                                <li class="text-xs text-gray-400">Belum ada riwayat revisi.</li>
                            @endforelse
                        </ul>
                    </div>

                    {{-- Diskusi (komentar berulir) --}}
                    <div class="border-t border-gray-100 pt-4">
                        <livewire:komentar :subjek-type="App\Models\TugasShot::class" :subjek-id="$task->id" :key="'kom-shot-'.$task->id" />
                    </div>
                </div>
            </div>
        </aside>
    @endif
</div>
