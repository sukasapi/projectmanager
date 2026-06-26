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
                        {{ $task->task_type->label() }}
                    </h2>
                    <p class="text-xs text-gray-500">{{ $task->shot->adegan->scene_name }}</p>
                </div>
                <button wire:click="tutup" class="text-gray-400 hover:text-gray-700">&times;</button>
            </div>

            <div class="grid flex-1 grid-cols-1 gap-0 overflow-y-auto md:grid-cols-2">
                {{-- Kolom kiri: preview video --}}
                <div class="border-r border-gray-200 p-5">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Preview</h3>

                    @if ($task->preview_url)
                        <video controls class="w-full rounded border border-gray-200 bg-black"
                               src="{{ $task->preview_url }}"></video>
                        <a href="{{ $task->preview_url }}" target="_blank"
                           class="mt-2 inline-block text-xs text-brand-600 hover:underline">Buka di tab baru ↗</a>
                    @else
                        <div class="flex h-40 items-center justify-center rounded border border-dashed border-gray-300 text-xs text-gray-400">
                            Belum ada preview.
                        </div>
                    @endif

                    <div class="mt-3 flex items-end gap-2">
                        <div class="flex-1">
                            <label class="mb-1 block text-xs font-medium text-gray-600">Tautan video (.mp4/.mov)</label>
                            <input type="url" wire:model="previewUrl" placeholder="https://drive.google.com/..."
                                   class="w-full rounded border-gray-300 text-sm shadow-sm">
                            @error('previewUrl') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <button wire:click="simpanPreview"
                                class="rounded bg-gray-800 px-3 py-2 text-xs font-medium text-white hover:bg-gray-900">
                            Simpan
                        </button>
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

                    {{-- Aksi transisi sesuai status saat ini --}}
                    <div class="flex flex-wrap gap-2">
                        @if ($task->status === \App\Enums\TaskStatus::NOT_STARTED)
                            <button wire:click="ubahStatus('IN_PROGRESS')"
                                    class="rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                                Mulai Kerjakan
                            </button>
                        @elseif ($task->status === \App\Enums\TaskStatus::IN_PROGRESS)
                            <button wire:click="ubahStatus('REVIEW')"
                                    class="rounded bg-amber-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-600">
                                Ajukan Review
                            </button>
                        @elseif ($task->status === \App\Enums\TaskStatus::REVIEW)
                            <button wire:click="ubahStatus('APPROVED')"
                                    class="rounded bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">
                                Setujui
                            </button>
                            <button wire:click="mintaRevisi"
                                    class="rounded bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                                Minta Revisi
                            </button>
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
                </div>
            </div>
        </aside>
    @endif
</div>
