<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Style Shotlist</h1>
            <p class="text-sm text-slate-500">Kelola beberapa style shotlist — tiap style punya susunan kolom sendiri. Team Lead/Admin memilih style per seri; berlaku untuk seluruh episodenya.</p>
        </div>
        <button wire:click="createStyle" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Tambah Style
        </button>
    </div>

    {{-- Pilihan style (tab) --}}
    <div class="flex flex-wrap gap-2">
        @foreach ($styles as $s)
            <div wire:key="style-{{ $s->id }}" class="group relative">
                <button wire:click="pilihStyle({{ $s->id }})"
                        class="inline-flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-medium transition {{ $styleId === $s->id ? 'border-brand-600 bg-brand-700 text-white shadow-sm' : 'border-slate-300 bg-white text-slate-600 hover:border-brand-400' }}">
                    {{ $s->name }}
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold {{ $styleId === $s->id ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $s->kolom_count }} kolom</span>
                    @if ($s->is_default)
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold {{ $styleId === $s->id ? 'bg-amber-300/90 text-amber-900' : 'bg-amber-100 text-amber-700' }}">default</span>
                    @endif
                </button>
            </div>
        @endforeach
    </div>

    @if ($styleAktif)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-800">{{ $styleAktif->name }}</span>
                    @if ($styleAktif->is_default)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">default</span>@endif
                    @if ($styleAktif->seri_count ?? false)<span class="text-[11px] text-slate-400">dipakai {{ $styleAktif->seri_count }} seri</span>@endif
                </div>
                @if ($styleAktif->description)<p class="text-[11px] text-slate-400">{{ $styleAktif->description }}</p>@endif
            </div>
            <div class="flex items-center gap-1.5">
                @unless ($styleAktif->is_default)
                    <button wire:click="setDefaultStyle({{ $styleAktif->id }})" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50" title="Jadikan default studio">Jadikan Default</button>
                @endunless
                <button wire:click="editStyle({{ $styleAktif->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 hover:bg-brand-100 hover:text-brand-700" title="Edit style">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </button>
                @unless ($styleAktif->is_default)
                    <button x-on:click="$confirm(@js('Hapus style “'.$styleAktif->name.'” beserta kolom-kolomnya?'), { danger: true }).then(ok => ok && $wire.deleteStyle({{ $styleAktif->id }}))" class="inline-flex rounded-md bg-slate-100 p-1.5 text-red-500 hover:bg-red-100 hover:text-red-700" title="Hapus style">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                @endunless
                <button wire:click="create" class="ml-2 inline-flex items-center gap-1.5 rounded-lg border border-brand-600 bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Tambah Kolom
                </button>
            </div>
        </div>
    @endif

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
                    <tr><td colspan="6" class="px-4 py-6 text-center text-xs text-slate-400">Belum ada kolom pada style ini — klik "Tambah Kolom".</td></tr>
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

    {{-- Modal style (tambah/edit) --}}
    @if ($showStyleForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="cancelStyle"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">{{ $editingStyleId ? 'Edit Style' : 'Tambah Style' }}</h2>
                    <button wire:click="cancelStyle" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <form wire:submit="saveStyle" class="space-y-4 px-5 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nama style</label>
                        <input type="text" wire:model="styleName" placeholder="mis. Cinematic Detail, Simple 2D" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('styleName') border-red-400 @enderror">
                        @error('styleName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi <span class="text-slate-400">(opsional)</span></label>
                        <input type="text" wire:model="styleDescription" placeholder="kapan style ini dipakai" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('styleDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="cancelStyle" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">{{ $editingStyleId ? 'Simpan' : 'Tambah' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
