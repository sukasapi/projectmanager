<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Seri / Judul</h1>
            <p class="text-sm text-slate-500">Wadah judul di atas Episode — kelompokkan episode & lihat progres agregat.</p>
        </div>
        @if ($bisaKelola)
            <button wire:click="create" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Tambah Seri
            </button>
        @endif
    </div>

    @forelse ($seri as $s)
        <div wire:key="seri-{{ $s->id }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <h2 class="font-semibold text-slate-800">{{ $s->name }}</h2>
                    @if ($s->description)<p class="text-xs text-slate-400">{{ $s->description }}</p>@endif
                    <p class="mt-0.5 text-[11px] text-slate-400">
                        {{ $s->episode->count() }} episode
                        · Style shotlist: <span class="font-medium text-slate-500">{{ $s->gayaShotlist?->name ?? 'Default studio' }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    @php $p = $progres[$s->id] ?? 0; @endphp
                    <div class="w-32">
                        <div class="flex justify-between text-[10px] text-slate-400"><span>Progres</span><span>{{ $p }}%</span></div>
                        <div class="mt-0.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-600" style="width: {{ $p }}%"></div></div>
                    </div>
                    @if ($bisaKelola)
                        <button wire:click="edit({{ $s->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 hover:bg-brand-100 hover:text-brand-700" title="Edit">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </button>
                        <button x-on:click="$confirm(@js('Hapus seri “'.$s->name.'”? Episode & aset tidak ikut terhapus (hanya dilepas dari seri).'), { danger: true }).then(ok => ok && $wire.delete({{ $s->id }}))" class="inline-flex rounded-md bg-slate-100 p-1.5 text-red-500 hover:bg-red-100 hover:text-red-700" title="Hapus">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    @endif
                </div>
            </div>
            @if ($s->episode->isNotEmpty())
                <div class="flex flex-wrap gap-2 px-4 py-3">
                    @foreach ($s->episode as $e)
                        <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-600">
                            {{ $e->name }}
                            <span class="rounded-full px-1.5 py-0.5 text-[9px] font-medium {{ $e->isClosed() ? 'bg-slate-200 text-slate-500' : ($e->isPublished() ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700') }}">{{ $e->isClosed() ? 'Closed' : ($e->isPublished() ? 'Published' : 'Draft') }}</span>
                        </span>
                    @endforeach
                </div>
            @else
                <p class="px-4 py-3 text-xs text-slate-400">Belum ada episode. Tautkan episode ke seri ini dari form Episode.</p>
            @endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">Belum ada seri.</div>
    @endforelse

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="cancel"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">{{ $editingId ? 'Edit Seri' : 'Tambah Seri' }}</h2>
                    <button wire:click="cancel" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <form wire:submit="save" class="space-y-4 px-5 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nama seri</label>
                        <input type="text" wire:model="name" placeholder="mis. Petualangan Bima" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi <span class="text-slate-400">(opsional)</span></label>
                        <textarea wire:model="description" rows="2" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Style shotlist</label>
                        <select wire:model="shotlistStyleId" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('shotlistStyleId') border-red-400 @enderror">
                            <option value="">— Default studio —</option>
                            @foreach ($daftarStyle as $st)
                                <option value="{{ $st->id }}">{{ $st->name }}@if ($st->is_default) (default)@endif</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Berlaku untuk shotlist seluruh episode pada seri ini.</p>
                        @error('shotlistStyleId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="cancel" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">{{ $editingId ? 'Simpan' : 'Tambah' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
