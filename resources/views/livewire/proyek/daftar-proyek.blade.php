<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Episode</h1>
            <p class="text-sm text-slate-500">Kelola Project/Episode produksi dan kaitkan dengan klien.</p>
        </div>
        <button wire:click="create"
                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Episode
        </button>
    </div>

    {{-- Daftar episode --}}
    @if ($episodes->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            Belum ada episode. Klik <span class="font-medium text-slate-700">Tambah Episode</span> untuk memulai.
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500">
                            <th class="px-4 py-2.5 text-left">Episode</th>
                            <th class="px-4 py-2.5 text-left">Klien</th>
                            <th class="px-4 py-2.5 text-left">Status</th>
                            <th class="px-4 py-2.5 text-right">Adegan</th>
                            <th class="px-4 py-2.5 text-right">Total Durasi</th>
                            <th class="px-4 py-2.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($episodes as $ep)
                            <tr wire:key="ep-{{ $ep->id }}" class="group transition hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ $ep->name }}</div>
                                    @if ($ep->description)
                                        <div class="mt-0.5 line-clamp-1 text-xs text-slate-400">{{ $ep->description }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $ep->klien?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $ep->status->color() }}">
                                        {{ $ep->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-600">{{ $ep->adegan_count }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-medium text-brand-700">{{ (int) $ep->total_durasi }}s</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="edit({{ $ep->id }})"
                                                class="rounded p-1.5 text-slate-400 transition hover:bg-brand-50 hover:text-brand-700" title="Edit">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click="delete({{ $ep->id }})"
                                                wire:confirm="Hapus episode &quot;{{ $ep->name }}&quot; beserta seluruh adegan, shot, aset, dan tugasnya?"
                                                class="rounded p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Hapus">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Modal form buat/edit --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="cancel"></div>

            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">
                        {{ $editingId ? 'Edit Episode' : 'Tambah Episode' }}
                    </h2>
                    <button wire:click="cancel" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>

                <form wire:submit="save" class="space-y-4 px-5 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nama Episode</label>
                        <input type="text" wire:model="name" placeholder="Cerita 23"
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                        <textarea wire:model="description" rows="2" placeholder="Ringkasan episode (opsional)"
                                  class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                            <select wire:model="status"
                                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @foreach ($daftarStatus as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Klien</label>
                            <select wire:model="clientId"
                                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">— tanpa klien —</option>
                                @foreach ($daftarKlien as $k)
                                    <option value="{{ $k->id }}">{{ $k->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="rounded-lg bg-slate-50 p-3">
                        <label class="mb-1 block text-xs font-medium text-slate-600">…atau tambah klien baru</label>
                        <input type="text" wire:model="newClientName" placeholder="Nama klien baru"
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('newClientName') border-red-400 @enderror">
                        @error('newClientName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-[11px] text-slate-400">Bila diisi, klien baru dibuat & dikaitkan otomatis.</p>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="cancel"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60">
                            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            {{ $editingId ? 'Simpan Perubahan' : 'Simpan Episode' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
