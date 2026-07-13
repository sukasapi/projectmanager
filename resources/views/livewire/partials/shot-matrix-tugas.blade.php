{{--
    Isi satu sel tugas (status + artis + tombol aksi) untuk satu shot × tahap.
    Dipakai tampilan tabel maupun tree Shot Matrix.
    Butuh: $shot, $tahap, $tahapKolom, $uid, $kelolaEpisode, $bisaReviewEpisode, $published
--}}
@php
    $tugas = $shot->tugasShot->first(fn ($t) => $t->tahap_id === $tahap->id);
    $milik = $tugas && $tugas->artists->contains($uid);
    $bisaBuka = $tugas && ($kelolaEpisode || $milik);
    $st = $tugas?->status;
    $btn = 'w-full rounded-md px-2 py-1 text-[11px] font-semibold transition';
    // Dependensi pipeline: tahap ini butuh prasyarat (tahap sebelumnya) APPROVED dulu.
    $prereqOk = true;
    $prereqNama = null;
    if ($tugas && $tahap->requires_tahap_id) {
        $pre = $shot->tugasShot->first(fn ($t) => $t->tahap_id === $tahap->requires_tahap_id);
        $prereqOk = $pre && $pre->status === \App\Enums\TaskStatus::APPROVED;
        $prereqNama = optional($tahapKolom->firstWhere('id', $tahap->requires_tahap_id))->name ?? 'tahap sebelumnya';
    }
@endphp
@if (! $tugas)
    <span class="block text-center text-[10px] italic text-slate-300" title="Shot ini tidak punya sub-task untuk tahap {{ $tahap->name }} — cek Konfigurasi Pipeline episode.">tidak ada tugas</span>
@else
    <div @class([
        'rounded-lg p-2',
        'ring-2 ring-brand-400 bg-brand-50/60' => $milik,
        'bg-slate-50/40' => ! $milik && $bisaBuka,
    ])>
        <div class="mb-1 flex flex-wrap items-center gap-1">
            <x-badge-status :status="$st" />
            @if ($milik)
                <span class="inline-flex items-center rounded-full bg-brand-600 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">Anda</span>
            @endif
        </div>

        @if ($tugas->artists->isNotEmpty())
            <p class="mb-1.5 truncate text-[11px] {{ $milik ? 'font-semibold text-brand-700' : 'text-slate-500' }}" title="{{ $tugas->artists->pluck('name')->join(', ') }}">{{ $tugas->artists->pluck('name')->join(', ') }}</p>
        @else
            <p class="mb-1.5 text-[11px] italic {{ $kelolaEpisode ? 'text-amber-500' : 'text-slate-300' }}">
                belum ada artis{{ $kelolaEpisode ? ' — pakai Assign Massal' : '' }}
            </p>
        @endif

        @if ($st === \App\Enums\TaskStatus::NOT_STARTED && $milik && ! $prereqOk)
            <x-tombol-aksi variant="mulai" block disabled :reason="'Menunggu '.$prereqNama.' disetujui'">▶ Mulai</x-tombol-aksi>
        @elseif ($st === \App\Enums\TaskStatus::NOT_STARTED && $milik && ! $published)
            <x-tombol-aksi variant="mulai" block disabled reason="Episode belum dipublish">▶ Mulai</x-tombol-aksi>
        @elseif ($st === \App\Enums\TaskStatus::NOT_STARTED && $milik)
            <x-tombol-aksi variant="mulai" block wire:click="mulaiShot({{ $tugas->id }})">▶ Mulai</x-tombol-aksi>
        @elseif ($st === \App\Enums\TaskStatus::NOT_STARTED && ! $prereqOk && ! $bisaBuka)
            <span class="flex w-full items-center justify-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-400" title="Selesaikan & setujui {{ $prereqNama }} dulu">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                Menunggu {{ $prereqNama }}
            </span>
        @elseif ($milik && $st === \App\Enums\TaskStatus::IN_PROGRESS)
            <x-tombol-aksi variant="review" block wire:click="review({{ $tugas->id }})">⤴ Ajukan Review</x-tombol-aksi>
        @elseif ($bisaReviewEpisode && $st === \App\Enums\TaskStatus::REVIEW)
            <x-tombol-aksi variant="setuju" block wire:click="review({{ $tugas->id }})">Tinjau Sekarang</x-tombol-aksi>
        @elseif ($milik && $st === \App\Enums\TaskStatus::REVIEW)
            <button wire:click="review({{ $tugas->id }})" title="Buka untuk lihat / perbarui kiriman selagi menunggu ditinjau"
                    class="{{ $btn }} flex items-center justify-center gap-1 border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Menunggu review
            </button>
        @elseif ($st === \App\Enums\TaskStatus::APPROVED)
            @if ($bisaBuka)
                <x-tombol-aksi variant="lihat" block wire:click="review({{ $tugas->id }})">✓ Lihat Hasil</x-tombol-aksi>
            @else
                <span class="block text-center text-[11px] font-semibold text-green-600">✓ Disetujui</span>
            @endif
        @elseif ($bisaBuka)
            <x-tombol-aksi variant="netral" block wire:click="review({{ $tugas->id }})">Buka Detail</x-tombol-aksi>
        @endif
    </div>
@endif
