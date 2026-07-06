<div class="space-y-5">
    @if (! $proyekId)
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Shotlist</h1>
            <p class="text-sm text-slate-500">Pilih episode untuk menyusun shotlist (isi manual / impor CSV) lalu generate ke Produksi.</p>
        </div>
        @if ($episodes->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">Belum ada episode.</div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($episodes as $ep)
                    <button wire:click="pilihEpisode({{ $ep->id }})" wire:key="ep-{{ $ep->id }}" class="group rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-brand-400 hover:shadow">
                        <div class="font-semibold text-slate-800 group-hover:text-brand-700">{{ $ep->name }}</div>
                    </button>
                @endforeach
            </div>
        @endif
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button wire:click="gantiEpisode" class="text-slate-400 hover:text-brand-700" title="Ganti episode">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Shotlist — {{ $proyek->name }}</h1>
                    <p class="text-sm text-slate-500">{{ $rows->count() }} baris · {{ $belumDigenerate }} belum di-generate ke Produksi.</p>
                </div>
            </div>
            @if ($bisaKelola)
                <div class="flex flex-wrap items-center gap-2">
                    <button wire:click="tambah" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Tambah Baris
                    </button>
                    @php
                        $genPesan = $shotlistDisetujui
                            ? 'Generate '.$belumDigenerate.' baris shotlist menjadi shot di Produksi?'
                            : 'Tahap Shotlist belum disetujui ('.($shotlistStatusLabel ?? 'belum ada').'). Tetap generate '.$belumDigenerate.' baris ke Produksi?';
                    @endphp
                    <button x-on:click="$confirm(@js($genPesan), { confirmText: 'Generate', icon: @js($shotlistDisetujui ? 'info' : 'warning') }).then(ok => ok && $wire.generate())"
                            @disabled($belumDigenerate === 0)
                            class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-50 {{ $shotlistDisetujui ? 'bg-brand-700 hover:bg-brand-800' : 'bg-amber-600 hover:bg-amber-700' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        Generate ke Produksi
                    </button>
                </div>
            @endif
        </div>

        {{-- Impor CSV --}}
        @if ($bisaKelola)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Impor CSV</label>
                        <input type="file" wire:model="csv" accept=".csv,text/csv" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        @error('csv') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-[11px] text-slate-400">Header CSV dicocokkan otomatis ke nama kolom (koma / titik-koma). Kolom yang tak cocok diabaikan.</p>
                    </div>
                    <button wire:click="unduhTemplate" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50" title="Unduh template CSV berisi header kolom studio">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Template
                    </button>
                    <button wire:click="importCsv" wire:loading.attr="disabled" wire:target="importCsv,csv" class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                        <span wire:loading.remove wire:target="importCsv">Impor</span>
                        <span wire:loading wire:target="importCsv">Mengimpor…</span>
                    </button>
                </div>
                @if ($shotlistStatusLabel)
                    <p class="mt-2 text-[11px] {{ $shotlistDisetujui ? 'text-green-600' : 'text-amber-600' }}">
                        Tahap Shotlist (Pra-Produksi): <span class="font-semibold">{{ $shotlistStatusLabel }}</span>{{ $shotlistDisetujui ? ' — siap di-generate.' : ' — sebaiknya disetujui dulu sebelum generate.' }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Tabel shotlist --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-2.5 text-left font-semibold">#</th>
                            @foreach ($kolom as $k)
                                <th class="whitespace-nowrap px-3 py-2.5 text-left font-semibold">
                                    {{ $k->label }}
                                    @if ($k->peran)<span class="ml-1 rounded bg-brand-100 px-1 py-0.5 text-[9px] font-semibold text-brand-700">{{ $k->peran->value }}</span>@endif
                                </th>
                            @endforeach
                            <th class="px-3 py-2.5 text-center font-semibold">Produksi</th>
                            @if ($bisaKelola)<th class="px-3 py-2.5 text-center font-semibold">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr wire:key="sl-{{ $row->id }}" class="align-top transition hover:bg-slate-50">
                                <td class="px-3 py-2.5 tabular-nums text-slate-400">{{ $row->urutan }}</td>
                                @foreach ($kolom as $k)
                                    <td class="max-w-[16rem] px-3 py-2.5 text-slate-700">
                                        <div class="line-clamp-3">{{ $row->data[$k->key] ?? '—' }}</div>
                                    </td>
                                @endforeach
                                <td class="px-3 py-2.5 text-center">
                                    @if ($row->shot_id)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-700">✓ dibuat</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700">belum</span>
                                    @endif
                                </td>
                                @if ($bisaKelola)
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="edit({{ $row->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 hover:bg-brand-100 hover:text-brand-700" title="Edit">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button x-on:click="$confirm(@js('Hapus baris shotlist ini?'), { danger: true }).then(ok => ok && $wire.hapus({{ $row->id }}))" class="inline-flex rounded-md bg-slate-100 p-1.5 text-red-500 hover:bg-red-100 hover:text-red-700" title="Hapus">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ 3 + $kolom->count() }}" class="px-4 py-6 text-center text-xs text-slate-400">Belum ada baris shotlist. Tambah manual atau impor CSV.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal baris --}}
        @if ($showForm)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-brand-950/60" wire:click="cancel"></div>
                <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-semibold text-slate-900">{{ $editingId ? 'Edit Baris' : 'Tambah Baris' }}</h2>
                        <button wire:click="cancel" class="text-slate-400 hover:text-slate-700">&times;</button>
                    </div>
                    <form wire:submit="save" class="space-y-3 px-5 py-5">
                        @foreach ($kolom as $k)
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">{{ $k->label }}@if ($k->peran)<span class="ml-1 text-[10px] text-brand-600">({{ $k->peran->value }})</span>@endif</label>
                                @if ($k->tipe === 'select')
                                    <select wire:model="baris.{{ $k->key }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        <option value="">—</option>
                                        @foreach ($k->opsi ?? [] as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
                                    </select>
                                @elseif ($k->tipe === 'number')
                                    <input type="number" wire:model="baris.{{ $k->key }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @else
                                    <input type="text" wire:model="baris.{{ $k->key }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @endif
                            </div>
                        @endforeach
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
