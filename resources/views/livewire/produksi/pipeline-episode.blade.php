<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('proyek') }}" wire:navigate class="text-slate-400 hover:text-brand-700" title="Kembali ke Episode">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </a>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Pipeline — {{ $proyek->name }}</h1>
            </div>
            <p class="text-sm text-slate-500">Susun tahapan produksi khusus episode ini. Perubahan hanya berlaku untuk episode <span class="font-medium">{{ $proyek->name }}</span>.</p>
        </div>
        <button wire:click="create"
                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Tambah Tahap
        </button>
    </div>

    @foreach ($fase as $f)
        @php $items = $grup[$f->value] ?? collect(); @endphp
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $f->color() }}">{{ $f->label() }}</span>
                    <span class="text-xs text-slate-400">{{ $items->count() }} tahap</span>
                </div>
            </div>
            @if ($items->isEmpty())
                <p class="px-4 py-6 text-center text-xs text-slate-400">Belum ada tahap pada fase ini.</p>
            @else
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-2.5 text-left font-semibold">#</th>
                            <th class="px-4 py-2.5 text-left font-semibold">Tahap</th>
                            <th class="px-4 py-2.5 text-left font-semibold">Level</th>
                            <th class="px-4 py-2.5 text-left font-semibold">Prasyarat</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Aktif</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($items as $t)
                            <tr wire:key="tahap-{{ $t->id }}" class="transition hover:bg-slate-50 {{ $t->is_active ? '' : 'opacity-60' }}">
                                <td class="px-4 py-2.5 tabular-nums text-slate-400">{{ $t->urutan }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="font-medium text-slate-800">{{ $t->name }}</div>
                                    @if ($t->description)<div class="text-xs text-slate-400">{{ Str::limit($t->description, 60) }}</div>@endif
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-500">{{ $t->level->label() }}</td>
                                <td class="px-4 py-2.5 text-xs text-slate-500">{{ $t->prasyarat?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <button wire:click="toggleAktif({{ $t->id }})" class="inline-flex">
                                        @if ($t->is_active)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Nonaktif</span>
                                        @endif
                                    </button>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="edit({{ $t->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 transition hover:bg-brand-100 hover:text-brand-700" title="Edit">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button x-on:click="$confirm(@js('Hapus tahap “'.$t->name.'”? (hanya jika belum dipakai tugas)'), { danger: true }).then(ok => ok && $wire.delete({{ $t->id }}))" class="inline-flex rounded-md bg-slate-100 p-1.5 text-red-500 transition hover:bg-red-100 hover:text-red-700" title="Hapus">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

    {{-- Modal form --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="cancel"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">{{ $editingId ? 'Edit Tahap' : 'Tambah Tahap' }}</h2>
                    <button wire:click="cancel" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <form wire:submit="save" class="space-y-4 px-5 py-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Fase</label>
                            <select wire:model="phase" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @foreach ($fase as $f)<option value="{{ $f->value }}">{{ $f->label() }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Level</label>
                            <select wire:model="level" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @foreach ($levels as $l)<option value="{{ $l->value }}">{{ $l->label() }}</option>@endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-slate-400">"Per Shot" = kolom di matriks Produksi.</p>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nama tahap</label>
                        <input type="text" wire:model="name" placeholder="mis. Animate" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Urutan</label>
                            <input type="number" min="0" wire:model="urutan" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Prasyarat <span class="text-slate-400">(opsional)</span></label>
                            <select wire:model="requiresTahapId" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">— tidak ada —</option>
                                @foreach ($kandidatPrasyarat as $k)
                                    @if ($k->id !== $editingId)<option value="{{ $k->id }}">{{ $k->phase->label() }} · {{ $k->name }}</option>@endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi <span class="text-slate-400">(opsional)</span></label>
                        <textarea wire:model="description" rows="2" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Tahap aktif
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
