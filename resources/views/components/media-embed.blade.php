@props(['url'])

@php
    $u = trim((string) $url);

    // Deteksi ID file Google Drive dari berbagai bentuk tautan share.
    $driveId = null;
    if (preg_match('~drive\.google\.com/file/d/([A-Za-z0-9_-]{10,})~', $u, $m)) {
        $driveId = $m[1];
    } elseif (preg_match('~drive\.google\.com/(?:open|uc)\?(?:[^&]*&)*id=([A-Za-z0-9_-]{10,})~', $u, $m)) {
        $driveId = $m[1];
    }

    // Google Docs/Sheets/Slides → bentuk /preview.
    $docEmbed = null;
    if (! $driveId && preg_match('~(docs\.google\.com/(?:document|spreadsheets|presentation)/d/[A-Za-z0-9_-]+)~', $u, $dm)) {
        $docEmbed = 'https://'.$dm[1].'/preview';
    }

    $embed = $driveId ? "https://drive.google.com/file/d/{$driveId}/preview" : $docEmbed;

    // URL media langsung (bisa diputar di tag <video>).
    $isMedia = (bool) preg_match('~\.(mp4|mov|m4v|webm|ogg)(\?|#|$)~i', $u);
@endphp

@if ($u === '')
    <div class="flex h-40 items-center justify-center rounded border border-dashed border-gray-300 text-xs text-gray-400">
        Belum ada preview.
    </div>
@else
    @if ($embed)
        <div class="aspect-video w-full overflow-hidden rounded border border-gray-200 bg-black">
            <iframe src="{{ $embed }}" class="h-full w-full" allow="autoplay" allowfullscreen loading="lazy"></iframe>
        </div>
    @elseif ($isMedia)
        <video controls class="w-full rounded border border-gray-200 bg-black" src="{{ $u }}"></video>
    @else
        <div class="flex h-32 flex-col items-center justify-center gap-1 rounded border border-dashed border-gray-300 px-3 text-center text-xs text-gray-500">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.656-1.328a4 4 0 010-5.656l3-3a4 4 0 015.656 5.656l-1.5 1.5" /></svg>
            Pratinjau inline tidak tersedia untuk tautan ini.
        </div>
    @endif

    <a href="{{ $u }}" target="_blank" rel="noopener"
       class="mt-2 inline-block text-xs text-brand-600 hover:underline">Buka di tab baru ↗</a>
@endif
