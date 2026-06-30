<div class="mx-auto max-w-5xl px-4 py-6">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Asset Library</h1>
        <p class="mt-1 text-sm text-slate-500">
            Seluruh tautan file yang disertakan pada tiap proses — Pra-Produksi, Produksi, Pasca-Produksi, dan Aset —
            dikelompokkan per episode.
        </p>
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
