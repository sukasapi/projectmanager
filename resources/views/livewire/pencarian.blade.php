<div class="mx-auto max-w-3xl px-4 py-6">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Pencarian</h1>
        <div class="relative mt-3">
            <svg class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input type="text" wire:model.live.debounce.300ms="q" autofocus placeholder="Cari episode, scene, shot, aset, atau artis…"
                   class="w-full rounded-lg border-slate-300 pl-10 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
    </div>

    @php
        $section = function ($judul, $items, $render) {
            return ['judul' => $judul, 'items' => $items, 'render' => $render];
        };
    @endphp

    @if (mb_strlen($term) < 2)
        <p class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-400">Ketik minimal 2 karakter untuk mencari.</p>
    @elseif ($total === 0)
        <p class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-400">Tidak ada hasil untuk "<span class="font-medium text-slate-600">{{ $term }}</span>".</p>
    @else
        <div class="space-y-5">
            @if ($hasil['episode']->isNotEmpty())
                <div>
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Episode</h2>
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        @foreach ($hasil['episode'] as $e)
                            <a href="{{ route('proyek') }}" wire:navigate wire:key="e-{{ $e->id }}" class="flex items-center justify-between border-b border-slate-50 px-4 py-2.5 text-sm transition last:border-0 hover:bg-slate-50">
                                <span class="font-medium text-slate-700">{{ $e->name }}</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $e->status->color() }}">{{ $e->status->label() }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($hasil['shot']->isNotEmpty())
                <div>
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Shot</h2>
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        @foreach ($hasil['shot'] as $s)
                            <a href="{{ route('shot-matrix') }}" wire:navigate wire:key="s-{{ $s->id }}" class="flex items-center justify-between border-b border-slate-50 px-4 py-2.5 text-sm transition last:border-0 hover:bg-slate-50">
                                <span class="font-medium text-slate-700">{{ $s->shot_code }}</span>
                                <span class="text-xs text-slate-400">{{ $s->adegan?->scene_name }} · {{ $s->adegan?->proyek?->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($hasil['scene']->isNotEmpty())
                <div>
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Scene</h2>
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        @foreach ($hasil['scene'] as $sc)
                            <a href="{{ route('shot-matrix') }}" wire:navigate wire:key="sc-{{ $sc->id }}" class="flex items-center justify-between border-b border-slate-50 px-4 py-2.5 text-sm transition last:border-0 hover:bg-slate-50">
                                <span class="font-medium text-slate-700">{{ $sc->scene_name }}</span>
                                <span class="text-xs text-slate-400">{{ $sc->proyek?->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($hasil['aset']->isNotEmpty())
                <div>
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Aset</h2>
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        @foreach ($hasil['aset'] as $a)
                            <a href="{{ route('aset') }}" wire:navigate wire:key="a-{{ $a->id }}" class="flex items-center justify-between border-b border-slate-50 px-4 py-2.5 text-sm transition last:border-0 hover:bg-slate-50">
                                <span class="font-medium text-slate-700">{{ $a->name }}</span>
                                <span class="text-xs text-slate-400">{{ $a->type?->label() }} · {{ $a->proyek?->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($hasil['artis']->isNotEmpty())
                <div>
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Artis</h2>
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        @foreach ($hasil['artis'] as $u)
                            <a href="{{ route('tim.detail', $u->id) }}" wire:navigate wire:key="u-{{ $u->id }}" class="flex items-center justify-between border-b border-slate-50 px-4 py-2.5 text-sm transition last:border-0 hover:bg-slate-50">
                                <span class="font-medium text-slate-700">{{ $u->name }}</span>
                                <span class="text-xs text-slate-400">{{ $u->email }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
