{{--
    Tombol aksi seragam — beda jelas dari badge status (solid/outline + shadow + hover).
    Bila $disabled, tombol TETAP tampil (abu-abu, ikon gembok) dengan $reason sebagai tooltip,
    supaya pengguna tahu aksi itu ada tapi belum bisa & kenapa.

    variant: mulai | review | setuju | tolak | primary | netral | lihat
--}}
@props(['variant' => 'primary', 'disabled' => false, 'reason' => null, 'block' => false])
@php
    $dasar = 'inline-flex items-center justify-center gap-1 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold transition'.($block ? ' w-full' : '');
    $gaya = match ($variant) {
        'mulai' => 'bg-blue-600 text-white shadow-sm hover:bg-blue-700',
        'review' => 'bg-amber-500 text-white shadow-sm hover:bg-amber-600',
        'setuju' => 'bg-green-600 text-white shadow-sm hover:bg-green-700',
        'tolak' => 'bg-red-600 text-white shadow-sm hover:bg-red-700',
        'netral' => 'border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50',
        'lihat' => 'border border-green-300 bg-white text-green-700 shadow-sm hover:bg-green-50',
        default => 'bg-brand-700 text-white shadow-sm hover:bg-brand-800',
    };
@endphp
@if ($disabled)
    <span class="{{ $block ? 'block w-full' : 'inline-block' }}" title="{{ $reason }}">
        <button type="button" disabled aria-disabled="true"
                class="{{ $dasar }} w-full cursor-not-allowed border border-dashed border-slate-300 bg-slate-50 text-slate-400">
            <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            {{ $slot }}
        </button>
        @if ($reason)
            <span class="mt-0.5 block text-center text-[10px] leading-tight text-slate-400">{{ $reason }}</span>
        @endif
    </span>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $dasar.' '.$gaya]) }}>{{ $slot }}</button>
@endif
