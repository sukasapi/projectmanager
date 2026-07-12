<div class="mx-auto max-w-5xl px-4 py-6">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Aset Management</h1>
            <p class="mt-1 text-sm text-slate-500">
                Seluruh tautan file yang disertakan pada tiap proses — Pra-Produksi, Produksi, Pasca-Produksi, dan Aset —
                dikelompokkan per episode.
            </p>
        </div>
        <a href="{{ route('aset-kelola') }}" wire:navigate
           class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800"
           title="CRUD aset (karakter/environment/properti) & breakdown ke shot">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            Kelola Aset
        </a>
    </div>

    @forelse ($pohon as $episode)
        <div class="mb-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" wire:key="ep-{{ $episode['label'] }}">
            <ul>
                <x-tree-node :node="$episode" :level="0" />
            </ul>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            Belum ada tautan file pada episode mana pun. Tambahkan tautan file/preview di tracker Pra, Produksi, atau Pasca.
        </div>
    @endforelse
</div>
