@props(['node', 'level' => 0])

@php $isLeaf = ! empty($node['url']); @endphp

<li class="relative">
    @if ($isLeaf)
        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 py-1 pl-5">
            <svg class="h-3.5 w-3.5 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.656-1.328a4 4 0 010-5.656l3-3a4 4 0 015.656 5.656l-1.5 1.5" /></svg>
            <a href="{{ $node['url'] }}" target="_blank" rel="noopener"
               class="text-sm font-medium text-brand-700 hover:underline">{{ $node['label'] }}</a>
            @if (! empty($node['badge']))
                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">{{ $node['badge'] }}</span>
            @endif
            @if (! empty($node['meta']))
                <span class="text-xs text-slate-400">{{ $node['meta'] }}</span>
            @endif
        </div>
    @else
        <div x-data="{ open: {{ $level < 2 ? 'true' : 'false' }} }">
            <button type="button" @click="open = ! open"
                    class="flex w-full items-center gap-1.5 rounded-md py-1 pl-1 pr-2 text-left transition hover:bg-slate-50">
                <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="open && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                <span @class([
                    'font-semibold text-slate-800' => $level === 0,
                    'font-medium text-slate-700' => $level === 1,
                    'text-slate-600' => $level >= 2,
                    'text-sm',
                ])>{{ $node['label'] }}</span>
                @if (! empty($node['badge']))
                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-medium text-brand-600">{{ $node['badge'] }}</span>
                @endif
            </button>
            <ul x-show="open" x-cloak class="ml-3 border-l border-slate-200 pl-2">
                @foreach ($node['children'] as $child)
                    <x-tree-node :node="$child" :level="$level + 1" wire:key="tn-{{ $level }}-{{ $loop->index }}-{{ $child['label'] }}" />
                @endforeach
            </ul>
        </div>
    @endif
</li>
