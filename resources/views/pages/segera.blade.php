<x-layouts.app :header="$judul" :title="$judul.' · AnimTrack'">
    <div class="mx-auto max-w-3xl">
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 text-violet-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $judul }}</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ $deskripsi }}</p>
            <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Modul dalam pengembangan
            </span>
            <div class="mt-6">
                <a href="{{ route('shot-matrix') }}" wire:navigate
                   class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700">
                    ← Kembali ke Shot Matrix
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
