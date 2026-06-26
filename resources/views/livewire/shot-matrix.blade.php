<div wire:poll.15s class="space-y-5">
    {{-- Header halaman --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Shot Pipeline Matrix</h1>
            <p class="text-sm text-slate-500">Lacak status tiap sub-pipeline; durasi scene terakumulasi otomatis.</p>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-xs font-medium text-slate-500">Proyek</label>
            <select wire:model.live="proyekId"
                    class="rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                @foreach ($daftarProyek as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Toolbar tambah shot --}}
    <form wire:submit="addShot"
          class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col">
            <label class="mb-1 text-xs font-medium text-slate-600">Scene</label>
            <select wire:model="newSceneId" class="w-44 rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">— pilih scene —</option>
                @foreach ($daftarAdegan as $a)
                    <option value="{{ $a->id }}">{{ $a->scene_name }}</option>
                @endforeach
            </select>
            @error('newSceneId') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
        </div>
        <div class="flex flex-col">
            <label class="mb-1 text-xs font-medium text-slate-600">Kode Shot</label>
            <input type="text" wire:model="newShotCode" placeholder="SC01_SH04"
                   class="w-40 rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @error('newShotCode') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
        </div>
        <div class="flex flex-col">
            <label class="mb-1 text-xs font-medium text-slate-600">Durasi (detik)</label>
            <input type="number" min="0" wire:model="newDurationSeconds"
                   class="w-28 rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @error('newDurationSeconds') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
        </div>
        <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Shot
        </button>
    </form>

    @if (! $proyek || $proyek->adegan->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            Belum ada scene/shot pada proyek ini.
        </div>
    @else
        {{-- Kartu matriks --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-500">
                            <th class="px-4 py-2.5 text-left font-semibold">Shot</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Durasi</th>
                            @foreach ($taskTypes as $type)
                                <th class="border-l border-slate-100 px-4 py-2.5 text-left font-semibold">{{ $type->value }}</th>
                            @endforeach
                            <th class="border-l border-slate-100 px-4 py-2.5 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>

                    @foreach ($proyek->adegan as $adegan)
                        <tbody class="divide-y divide-slate-100">
                            {{-- Judul scene --}}
                            <tr class="bg-brand-50/60">
                                <td colspan="2" class="px-4 py-2 text-sm font-semibold text-brand-900">
                                    {{ $adegan->scene_name }}
                                </td>
                                <td colspan="{{ count($taskTypes) }}" class="px-4 py-2 text-xs text-brand-600">
                                    {{ $adegan->shot->count() }} shot
                                </td>
                                <td class="bg-brand-50/60"></td>
                            </tr>

                            @forelse ($adegan->shot as $shot)
                                <tr wire:key="shot-{{ $shot->id }}" class="group transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-4 py-2 font-mono font-medium text-slate-800">{{ $shot->shot_code }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right tabular-nums text-slate-500">{{ $shot->duration_seconds }}s</td>

                                    @foreach ($taskTypes as $type)
                                        @php $tugas = $shot->tugasShot->first(fn ($t) => $t->task_type === $type); @endphp
                                        <td class="border-l border-slate-100 px-3 py-2 align-top">
                                            @if ($tugas)
                                                <button type="button" wire:click="review({{ $tugas->id }})"
                                                        class="flex w-full flex-col items-start gap-1 rounded-md p-1 text-left transition hover:bg-brand-50">
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $tugas->status->color() }}">
                                                        {{ $tugas->status->label() }}
                                                    </span>
                                                    @if ($tugas->artists->isNotEmpty())
                                                        <span class="text-[11px] text-slate-500">{{ $tugas->artists->pluck('name')->join(', ') }}</span>
                                                    @endif
                                                </button>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="border-l border-slate-100 px-3 py-2 text-center">
                                        <button type="button" wire:click="deleteShot({{ $shot->id }})"
                                                wire:confirm="Hapus shot {{ $shot->shot_code }} beserta seluruh sub-task-nya?"
                                                class="rounded p-1 text-slate-400 opacity-0 transition hover:bg-red-50 hover:text-red-600 group-hover:opacity-100">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ 3 + count($taskTypes) }}" class="px-4 py-3 text-slate-400">Belum ada shot.</td></tr>
                            @endforelse

                            {{-- Subtotal durasi scene --}}
                            <tr class="bg-slate-50/80 font-medium text-slate-600">
                                <td class="px-4 py-2 text-right">Total {{ $adegan->scene_name }}</td>
                                <td class="px-4 py-2 text-right tabular-nums font-semibold text-brand-700">{{ $adegan->total_duration }}s</td>
                                <td colspan="{{ count($taskTypes) + 1 }}"></td>
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

    {{-- Panel review --}}
    <livewire:review-panel />
</div>
