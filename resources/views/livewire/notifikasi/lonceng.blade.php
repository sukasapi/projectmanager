<div x-data="{ open: false }" class="relative" wire:poll.60s>
    <button @click="open = !open" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100" title="Notifikasi">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($belum > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $belum > 9 ? '9+' : $belum }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" x-transition
         class="absolute right-0 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
            <span class="text-sm font-semibold text-slate-700">Notifikasi</span>
            @if ($belum > 0)
                <button wire:click="tandaiSemua" class="text-[11px] text-brand-600 hover:underline">Tandai semua dibaca</button>
            @endif
        </div>
        <div class="max-h-96 overflow-y-auto divide-y divide-slate-100">
            @forelse ($items as $n)
                <button wire:key="notif-{{ $n->id }}" wire:click="buka({{ $n->id }})"
                        class="block w-full px-4 py-2.5 text-left transition hover:bg-slate-50 {{ $n->read_at ? '' : 'bg-brand-50/40' }}">
                    <div class="flex items-start gap-2">
                        @unless ($n->read_at)<span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>@endunless
                        <div class="{{ $n->read_at ? 'ml-3.5' : '' }}">
                            <p class="text-sm font-medium text-slate-700">{{ $n->title }}</p>
                            @if ($n->message)<p class="mt-0.5 text-xs text-slate-500">{{ $n->message }}</p>@endif
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $n->created_at?->locale('id')->diffForHumans() }}</p>
                        </div>
                    </div>
                </button>
            @empty
                <p class="px-4 py-8 text-center text-xs text-slate-400">Belum ada notifikasi.</p>
            @endforelse
        </div>
        <a href="{{ route('notifikasi') }}" wire:navigate
           class="block border-t border-slate-100 px-4 py-2.5 text-center text-xs font-semibold text-brand-600 transition hover:bg-slate-50">
            Lihat semua notifikasi
        </a>
    </div>
</div>
