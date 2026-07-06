<div class="space-y-5">
    @if (! $proyekId)
        {{-- Pemilih episode (kartu) --}}
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Kelola Aset</h1>
            <p class="text-sm text-slate-500">Pilih episode untuk mengelola aset (karakter, environment, property) & breakdown ke shot.</p>
        </div>
        @if ($episodes->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">Belum ada episode.</div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($episodes as $ep)
                    <button wire:click="pilihEpisode({{ $ep->id }})" wire:key="ep-{{ $ep->id }}"
                            class="group rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-brand-400 hover:shadow">
                        <div class="font-semibold text-slate-800 group-hover:text-brand-700">{{ $ep->name }}</div>
                        <div class="mt-1 text-xs text-slate-400">{{ $ep->aset_count }} aset</div>
                    </button>
                @endforeach
            </div>
        @endif
    @else
        {{-- Header episode --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button wire:click="gantiEpisode" class="text-slate-400 hover:text-brand-700" title="Ganti episode">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Aset — {{ $proyek->name }}</h1>
                    <p class="text-sm text-slate-500">Karakter, environment & property beserta breakdown shot.</p>
                </div>
            </div>
            @if ($bisaKelola)
                <button wire:click="create" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Tambah Aset
                </button>
            @endif
        </div>

        @foreach ($tipeOpsi as $tipe)
            @php $items = $asetPerTipe[$tipe->value] ?? collect(); @endphp
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ $tipe->label() }}</span>
                    <span class="text-xs text-slate-400">{{ $items->count() }} aset</span>
                </div>
                @if ($items->isEmpty())
                    <p class="px-4 py-6 text-center text-xs text-slate-400">Belum ada aset {{ $tipe->label() }}.</p>
                @else
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-2.5 text-left font-semibold">Aset</th>
                                <th class="px-4 py-2.5 text-left font-semibold">Tahap</th>
                                <th class="px-4 py-2.5 text-left font-semibold">Artis</th>
                                <th class="px-4 py-2.5 text-left font-semibold">Status</th>
                                <th class="px-4 py-2.5 text-left font-semibold">Dipakai shot</th>
                                <th class="px-4 py-2.5 text-center font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($items as $a)
                                <tr wire:key="aset-{{ $a->id }}" class="transition hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-medium text-slate-800">{{ $a->name }}</span>
                                            @if ($a->series_id)<span class="rounded-full bg-indigo-100 px-1.5 py-0.5 text-[9px] font-semibold text-indigo-700" title="Aset bersama seri (dipakai lintas episode)">bersama</span>@endif
                                        </div>
                                        @if ($a->file_url)<a href="{{ $a->file_url }}" target="_blank" rel="noopener" class="text-[11px] text-brand-600 hover:underline">file</a>@endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500">{{ $a->task->label() }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $a->artist?->name ?? '—' }}</td>
                                    <td class="px-4 py-3"><span class="inline-flex items-center rounded px-2 py-0.5 text-[11px] font-medium {{ $a->status->color() }}">{{ $a->status->label() }}</span></td>
                                    <td class="px-4 py-3">
                                        @if ($a->shots->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($a->shots->take(5) as $s)<span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-600">{{ $s->shot_code }}</span>@endforeach
                                                @if ($a->shots->count() > 5)<span class="text-[10px] text-slate-400">+{{ $a->shots->count() - 5 }}</span>@endif
                                            </div>
                                        @else
                                            <span class="text-[11px] text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            @if ($bisaKelola)
                                                <button wire:click="edit({{ $a->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 hover:bg-brand-100 hover:text-brand-700" title="Edit">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button x-on:click="$confirm(@js('Hapus aset “'.$a->name.'”?'), { danger: true }).then(ok => ok && $wire.delete({{ $a->id }}))" class="inline-flex rounded-md bg-slate-100 p-1.5 text-red-500 hover:bg-red-100 hover:text-red-700" title="Hapus">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            @else
                                                <span class="text-[11px] text-slate-300">—</span>
                                            @endif
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
                <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-semibold text-slate-900">{{ $editingId ? 'Edit Aset' : 'Tambah Aset' }}</h2>
                        <button wire:click="cancel" class="text-slate-400 hover:text-slate-700">&times;</button>
                    </div>
                    <form wire:submit="save" class="space-y-4 px-5 py-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Nama aset</label>
                                <input type="text" wire:model="name" placeholder="mis. Bima" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror">
                                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Tipe</label>
                                <select wire:model="type" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @foreach ($tipeOpsi as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Tahap</label>
                                <select wire:model="task" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @foreach ($taskOpsi as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                                <select wire:model="status" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('status') border-red-400 @enderror">
                                    @foreach ($statusOpsi as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
                                </select>
                                @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Artis (PIC)</label>
                            <select wire:model="artistId" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">— belum ditentukan —</option>
                                @foreach ($daftarArtis as $a)<option value="{{ $a->id }}">{{ $a->name }} · {{ $a->role ?? '—' }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tautan file (Drive)</label>
                            <input type="url" wire:model="fileUrl" placeholder="https://drive.google.com/..." class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('fileUrl') border-red-400 @enderror">
                            @error('fileUrl') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Breakdown — shot yang memakai aset ini</label>
                            <div class="max-h-40 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2">
                                @forelse ($daftarShot as $s)
                                    <label class="flex cursor-pointer items-center gap-2 rounded px-1.5 py-1 text-xs text-slate-700 hover:bg-slate-50">
                                        <input type="checkbox" wire:model="shotIds" value="{{ $s->id }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        <span class="font-medium">{{ $s->shot_code }}</span>
                                    </label>
                                @empty
                                    <p class="px-1 py-1 text-[11px] text-slate-400">Episode belum punya shot.</p>
                                @endforelse
                            </div>
                        </div>
                        @if ($punyaSeri)
                            <label class="flex items-center gap-2 rounded-lg bg-indigo-50 px-3 py-2 text-sm text-indigo-800">
                                <input type="checkbox" wire:model="bersama" class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                                Aset bersama seri <span class="text-xs text-indigo-500">(dapat dipakai & dikelola dari semua episode dalam seri ini)</span>
                            </label>
                        @endif
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Catatan <span class="text-slate-400">(opsional)</span></label>
                            <textarea wire:model="notes" rows="2" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        </div>
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
