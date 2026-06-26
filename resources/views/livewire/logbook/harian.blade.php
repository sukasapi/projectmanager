<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Logbook</h1>
            <p class="text-sm text-slate-500">Catat pekerjaan harian Anda. Total tercatat: <span class="font-medium text-brand-700">{{ intdiv($totalMenit, 60) }}j {{ $totalMenit % 60 }}m</span>.</p>
        </div>
        <button wire:click="create"
                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Entri
        </button>
    </div>

    @if ($entri->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            Belum ada entri logbook. Klik <span class="font-medium text-slate-700">Tambah Entri</span> untuk mulai mencatat.
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500">
                            <th class="px-4 py-2.5 text-left">Tanggal</th>
                            <th class="px-4 py-2.5 text-left">Waktu</th>
                            <th class="px-4 py-2.5 text-right">Durasi</th>
                            <th class="px-4 py-2.5 text-left">Pekerjaan</th>
                            <th class="px-4 py-2.5 text-left">Tugas</th>
                            <th class="px-4 py-2.5 text-left">Status</th>
                            <th class="px-4 py-2.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($entri as $e)
                            <tr wire:key="log-{{ $e->id }}" class="align-top transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $e->tanggal->timezone($tz)->translatedFormat('D, d M') }}</td>
                                <td class="px-4 py-3 tabular-nums text-slate-600">
                                    {{ $e->jam_mulai->timezone($tz)->format('H:i') }}–{{ $e->jam_selesai->timezone($tz)->format('H:i') }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-medium text-slate-700">{{ intdiv($e->durasi_menit, 60) }}j {{ $e->durasi_menit % 60 }}m</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="max-w-xs whitespace-pre-line">{{ $e->deskripsi }}</div>
                                    @if ($e->output_url)
                                        <a href="{{ $e->output_url }}" target="_blank" rel="noopener" class="mt-0.5 inline-block text-xs text-brand-600 hover:underline">lihat output ↗</a>
                                    @endif
                                    @if ($e->review_notes)
                                        <div class="mt-1 rounded bg-slate-50 px-2 py-1 text-[11px] text-slate-500"><span class="font-medium">Catatan reviewer:</span> {{ $e->review_notes }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ $e->tugasShot?->shot?->shot_code ? $e->tugasShot->shot->shot_code.' · '.$e->tugasShot->task_type->label() : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $e->status->color() }}">{{ $e->status->label() }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($e->status->dapatDiedit())
                                            <button wire:click="submit({{ $e->id }})" wire:confirm="Kirim entri ini untuk direview? Setelah dikirim tidak bisa diubah."
                                                    class="rounded px-2 py-1 text-xs font-medium text-green-700 transition hover:bg-green-50" title="Kirim untuk review">Kirim</button>
                                            <button wire:click="edit({{ $e->id }})" class="rounded p-1.5 text-slate-400 transition hover:bg-brand-50 hover:text-brand-700" title="Edit">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button wire:click="delete({{ $e->id }})" wire:confirm="Hapus entri logbook ini?" class="rounded p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Hapus">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        @else
                                            <span class="text-[11px] text-slate-300">terkunci</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Modal form --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="cancel"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">{{ $editingId ? 'Edit Entri' : 'Tambah Entri Logbook' }}</h2>
                    <button wire:click="cancel" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>

                <form wire:submit="save" class="space-y-4 px-5 py-5">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label>
                            <input type="date" wire:model="tanggal" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('tanggal') border-red-400 @enderror">
                            @error('tanggal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Mulai</label>
                            <input type="time" wire:model="jamMulai" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('jamMulai') border-red-400 @enderror">
                            @error('jamMulai') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Selesai</label>
                            <input type="time" wire:model="jamSelesai" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('jamSelesai') border-red-400 @enderror">
                            @error('jamSelesai') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tugas terkait <span class="text-slate-400">(opsional)</span></label>
                        <select wire:model="shotTaskId" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">— tidak terkait shot-task —</option>
                            @foreach ($tugasSaya as $t)
                                <option value="{{ $t->id }}">{{ $t->shot?->shot_code }} · {{ $t->task_type->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi pekerjaan</label>
                        <textarea wire:model="deskripsi" rows="3" placeholder="Apa yang Anda kerjakan?"
                                  class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('deskripsi') border-red-400 @enderror"></textarea>
                        @error('deskripsi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tautan output <span class="text-slate-400">(opsional)</span></label>
                        <input type="url" wire:model="outputUrl" placeholder="https://…"
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('outputUrl') border-red-400 @enderror">
                        @error('outputUrl') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="cancel" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60">
                            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Simpan Draft
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
