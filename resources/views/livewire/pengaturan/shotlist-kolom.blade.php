<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Konfigurasi Kolom Shotlist</h1>
            <p class="text-sm text-slate-500">Sesuaikan kolom shotlist dengan kebutuhan studio. Tandai peran kolom agar dapat di-generate jadi shot.</p>
        </div>
        <button wire:click="create" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Tambah Kolom
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-2.5 text-left font-semibold">#</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Kolom</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Tipe</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Peran</th>
                    <th class="px-4 py-2.5 text-center font-semibold">Aktif</th>
                    <th class="px-4 py-2.5 text-center font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($kolom as $k)
                    <tr wire:key="kol-{{ $k->id }}" class="transition hover:bg-slate-50 {{ $k->is_active ? '' : 'opacity-60' }}">
                        <td class="px-4 py-2.5 tabular-nums text-slate-400">{{ $k->urutan }}</td>
                        <td class="px-4 py-2.5">
                            <div class="font-medium text-slate-800">{{ $k->label }}</div>
                            <div class="text-[11px] text-slate-400">{{ $k->key }}@if ($k->tipe === 'select' && $k->opsi) · {{ implode(', ', $k->opsi) }}@endif</div>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-slate-500">{{ ucfirst($k->tipe) }}</td>
                        <td class="px-4 py-2.5 text-xs">
                            @if ($k->peran)<span class="rounded-full bg-brand-100 px-2 py-0.5 font-medium text-brand-700">{{ $k->peran->label() }}</span>@else<span class="text-slate-400">metadata</span>@endif
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <button wire:click="toggleAktif({{ $k->id }})">
                                @if ($k->is_active)<span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700">Aktif</span>@else<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Nonaktif</span>@endif
                            </button>
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="edit({{ $k->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 hover:bg-brand-100 hover:text-brand-700" title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button x-on:click="$confirm(@js('Hapus kolom “'.$k->label.'”?'), { danger: true }).then(ok => ok && $wire.delete({{ $k->id }}))" class="inline-flex rounded-md bg-slate-100 p-1.5 text-red-500 hover:bg-red-100 hover:text-red-700" title="Hapus">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-xs text-slate-400">Belum ada kolom.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="cancel"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">{{ $editingId ? 'Edit Kolom' : 'Tambah Kolom' }}</h2>
                    <button wire:click="cancel" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <form wire:submit="save" class="space-y-4 px-5 py-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Nama kolom</label>
                            <input type="text" wire:model="label" placeholder="mis. Detail Visual" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('label') border-red-400 @enderror">
                            @error('label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Urutan</label>
                            <input type="number" min="0" wire:model="urutan" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tipe</label>
                            <select wire:model.live="tipe" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="text">Teks</option>
                                <option value="number">Angka</option>
                                <option value="select">Pilihan</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Peran <span class="text-slate-400">(opsional)</span></label>
                            <select wire:model="peran" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('peran') border-red-400 @enderror">
                                <option value="">— metadata —</option>
                                @foreach ($peranOpsi as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach
                            </select>
                            @error('peran') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @if ($tipe === 'select')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Pilihan <span class="text-slate-400">(pisahkan dengan koma)</span></label>
                            <input type="text" wire:model="opsiText" placeholder="ELS, LS, MS, CU" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    @endif
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Kolom aktif
                    </label>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="cancel" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">{{ $editingId ? 'Simpan' : 'Tambah' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
