{{--
    Badge status (BUKAN tombol): pill pucat + titik indikator, cursor-default.
    Pakai: <x-badge-status :status="$st" /> atau <x-badge-status label="Draft" color="bg-gray-100 text-gray-600" />
--}}
@props(['status' => null, 'label' => null, 'color' => null])
@php
    $warna = $color ?? ($status?->color() ?? 'bg-slate-100 text-slate-500');
    $teks = $label ?? ($status?->label() ?? '—');
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex cursor-default select-none items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium '.$warna]) }}>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-60"></span>{{ $teks }}
</span>
