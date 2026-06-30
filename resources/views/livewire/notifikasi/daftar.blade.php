<div class="mx-auto max-w-3xl px-4 py-6">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Notifikasi</h1>
            <p class="mt-1 text-sm text-slate-500">
                @if ($belum > 0)
                    <span class="font-semibold text-brand-700">{{ $belum }}</span> belum dibaca.
                @else
                    Semua notifikasi sudah dibaca.
                @endif
            </p>
        </div>
        @if ($belum > 0)
            <button wire:click="tandaiSemua"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                Tandai semua dibaca
            </button>
        @endif
    </div>

    @php
        $warna = [
            'publish' => ['bg-blue-100 text-blue-600', 'M12 8v4l3 3'],
            'propose' => ['bg-amber-100 text-amber-600', 'M12 8v4l3 3'],
            'approve' => ['bg-green-100 text-green-600', 'M5 13l4 4L19 7'],
            'reject' => ['bg-red-100 text-red-600', 'M6 18L18 6M6 6l12 12'],
            'info' => ['bg-slate-100 text-slate-500', 'M13 16h-1v-4h-1m1-4h.01'],
        ];
    @endphp

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($items as $n)
                @php $w = $warna[$n->type] ?? $warna['info']; @endphp
                <button wire:key="notif-{{ $n->id }}" wire:click="buka({{ $n->id }})"
                        class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50 {{ $n->read_at ? '' : 'bg-brand-50/40' }}">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $w[0] }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $w[1] }}" /></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $n->title }}</p>
                            @unless ($n->read_at)<span class="h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>@endunless
                        </div>
                        @if ($n->message)<p class="mt-0.5 text-sm text-slate-500">{{ $n->message }}</p>@endif
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $n->created_at?->locale('id')->isoFormat('D MMM Y, HH:mm') }} · {{ $n->created_at?->locale('id')->diffForHumans() }}</p>
                    </div>
                </button>
            @empty
                <p class="px-4 py-16 text-center text-sm text-slate-400">Belum ada notifikasi.</p>
            @endforelse
        </div>
    </div>

    @if ($items->hasPages())
        <div class="mt-4">{{ $items->links() }}</div>
    @endif
</div>
