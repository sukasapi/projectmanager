<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Episode</h1>
            <p class="text-sm text-slate-500">Kelola Project/Episode produksi dan kaitkan dengan klien.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('manage-tim')
                <a href="{{ route('seri') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                   title="Kelola seri (judul serial) untuk mengelompokkan episode">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h10M4 18h10" /></svg>
                    Kelola Seri
                </a>
            @endcan
            @if ($bisaKelola)
                <button wire:click="create"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Episode
                </button>
            @endif
        </div>
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
                            <th class="px-4 py-2.5 text-left">Klien / Team Lead</th>
                            <th class="px-4 py-2.5 text-left">Lifecycle</th>
                            <th class="px-4 py-2.5 text-right">Adegan</th>
                            <th class="px-4 py-2.5 text-right">Total Durasi</th>
                            <th class="px-4 py-2.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($episodes as $ep)
                            @php
                                $kelola = $ep->dapatDikelola(auth()->user());
                                $life = $ep->isClosed()
                                    ? ['Closed', 'bg-slate-200 text-slate-600']
                                    : ($ep->isPublished() ? ['Published', 'bg-green-100 text-green-700'] : ['Draft / Setup', 'bg-amber-100 text-amber-700']);
                            @endphp
                            <tr wire:key="ep-{{ $ep->id }}" class="group transition hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ $ep->name }}</div>
                                    @if ($ep->description)
                                        <div class="mt-0.5 line-clamp-1 text-xs text-slate-400">{{ $ep->description }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div>{{ $ep->klien?->name ?? '—' }}</div>
                                    <div class="text-xs text-slate-400">Lead: {{ $ep->teamLead?->name ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $life[1] }}">{{ $life[0] }}</span>
                                    @if ($ep->published_at)
                                        <div class="mt-0.5 text-[11px] text-slate-400">{{ $ep->published_at->locale('id')->isoFormat('D MMM Y') }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-600">{{ $ep->adegan_count }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-medium text-brand-700">{{ (int) $ep->total_durasi }}s</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($kelola && $ep->isDraft())
                                            <button x-on:click="$confirm(@js('Publish episode “'.$ep->name.'”? Semua artis yang ditugaskan akan diberi notifikasi.'), { confirmText: 'Publish', icon: 'info' }).then(ok => ok && $wire.publish({{ $ep->id }}))"
                                                    class="rounded-md bg-green-600 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-green-700">Publish</button>
                                        @elseif ($kelola && $ep->isPublished())
                                            <button x-on:click="$confirm(@js('Tandai episode “'.$ep->name.'” selesai (closed)?'), { confirmText: 'Selesaikan' }).then(ok => ok && $wire.tutup({{ $ep->id }}))"
                                                    class="rounded-md bg-brand-700 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-800">Selesai</button>
                                        @elseif ($ep->isClosed() && $bisaKelola)
                                            <button x-on:click="$confirm(@js('Buka kembali episode “'.$ep->name.'”? Episode dapat diedit & dilanjutkan lagi.'), { confirmText: 'Buka Kembali', icon: 'info' }).then(ok => ok && $wire.bukaKembali({{ $ep->id }}))"
                                                    class="rounded-md bg-amber-500 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-600">Buka Kembali</button>
                                        @endif
                                        @if ($kelola)
                                            <a href="{{ route('proyek.pipeline', $ep->id) }}" wire:navigate
                                               class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 transition hover:bg-brand-100 hover:text-brand-700" title="Atur pipeline episode">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </a>
                                        @endif
                                        <a href="{{ route('proyek.bible', $ep->id) }}" target="_blank"
                                           class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 transition hover:bg-gold-100 hover:text-gold-600" title="Unduh Production Bible (PDF)">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </a>
                                        @if ($bisaKelola)
                                            <button wire:click="edit({{ $ep->id }})"
                                                    class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 transition hover:bg-brand-100 hover:text-brand-700" title="Edit">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button x-on:click="$confirm(@js('Hapus episode “'.$ep->name.'” beserta seluruh adegan, shot, aset, dan tugasnya?'), { danger: true }).then(ok => ok && $wire.delete({{ $ep->id }}))"
                                                    class="inline-flex rounded-md bg-slate-100 p-1.5 text-red-500 transition hover:bg-red-100 hover:text-red-700" title="Hapus">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
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

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Team Lead <span class="text-slate-400">(penanggung jawab episode)</span></label>
                        <select wire:model="teamLeadId" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">— belum ditentukan —</option>
                            @foreach ($daftarArtis as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Team Lead dapat ikut setup, publish, approve, dan menutup episode ini.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Seri / Judul <span class="text-slate-400">(opsional)</span></label>
                        <select wire:model="seriId" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">— tanpa seri —</option>
                            @foreach ($daftarSeri as $sr)
                                <option value="{{ $sr->id }}">{{ $sr->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Kelompokkan episode di bawah satu judul untuk pandangan & reuse aset lintas-episode.</p>
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
