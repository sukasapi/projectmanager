{{--
    Stepper lifecycle episode: Draft → Setup → Published → Selesai.
    Menunjukkan posisi episode & langkah berikutnya, plus banner aksi bila masih Draft.
    Pakai: <x-stepper-episode :proyek="$proyek" :dapat-kelola="$dapatKelola" />
--}}
@props(['proyek', 'dapatKelola' => false])
@php
    $langkah = [
        ['key' => 'draft', 'label' => 'Draft', 'hint' => 'Episode dibuat'],
        ['key' => 'setup', 'label' => 'Setup', 'hint' => 'Assign artis & scene/shot'],
        ['key' => 'published', 'label' => 'Published', 'hint' => 'Tim mulai bekerja'],
        ['key' => 'closed', 'label' => 'Selesai', 'hint' => 'Episode ditutup'],
    ];
    // Draft = langkah setup (index 1); published = 2; closed = 3.
    $aktif = $proyek->isClosed() ? 3 : ($proyek->isPublished() ? 2 : 1);
@endphp
<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm']) }}>
    <ol class="flex flex-wrap items-center gap-x-1 gap-y-2">
        @foreach ($langkah as $i => $l)
            <li class="flex items-center gap-1.5" title="{{ $l['hint'] }}">
                <span @class([
                    'flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold',
                    'bg-green-600 text-white' => $i < $aktif,
                    'bg-brand-700 text-white ring-2 ring-brand-200' => $i === $aktif,
                    'bg-slate-100 text-slate-400' => $i > $aktif,
                ])>
                    @if ($i < $aktif)
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    @else
                        {{ $i + 1 }}
                    @endif
                </span>
                <span @class([
                    'text-xs',
                    'font-semibold text-slate-800' => $i === $aktif,
                    'font-medium text-green-700' => $i < $aktif,
                    'text-slate-400' => $i > $aktif,
                ])>{{ $l['label'] }}</span>
            </li>
            @unless ($loop->last)
                <li aria-hidden="true" class="mx-1 h-px w-6 shrink-0 {{ $i < $aktif ? 'bg-green-400' : 'bg-slate-200' }}"></li>
            @endunless
        @endforeach
        <li class="ml-auto text-[11px] text-slate-400">
            @if ($proyek->isDraft())
                Episode masih <span class="font-semibold text-slate-600">Draft</span> — tombol kerja (Mulai/Ajukan Review) aktif setelah dipublish.
            @elseif ($proyek->isClosed())
                Episode <span class="font-semibold text-slate-600">selesai</span> — read-only.
            @else
                Dipublish {{ $proyek->published_at?->locale('id')->isoFormat('D MMM Y') }}.
            @endif
        </li>
    </ol>

    @if ($proyek->isDraft())
        <div class="mt-3 flex flex-wrap items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5">
            <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <p class="min-w-0 flex-1 text-xs text-amber-800">
                <span class="font-semibold">Alur:</span> assign artis ke tahap/shot dulu (saat Draft), lalu <span class="font-semibold">Publish</span> episode di menu Episode — artis dinotifikasi & tombol kerja aktif.
            </p>
            @if ($dapatKelola)
                <a href="{{ route('proyek') }}" wire:navigate
                   class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-700">
                    Buka menu Episode
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </a>
            @endif
        </div>
    @endif
</div>
