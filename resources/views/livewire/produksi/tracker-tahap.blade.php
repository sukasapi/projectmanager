<div class="space-y-5" wire:poll.30s>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ $fase->label() }}</h1>
            <p class="text-sm text-slate-500">
                @if ($proyek)
                    Episode: <span class="font-medium text-slate-700">{{ $proyek->name }}</span> · lacak status tiap tahap {{ strtolower($fase->label()) }}.
                @else
                    Pilih episode yang akan dikerjakan.
                @endif
            </p>
        </div>
        @if ($proyek)
            <button wire:click="gantiEpisode"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                Ganti Episode
            </button>
        @endif
    </div>

    @if (! $proyek)
        {{-- Pemilih episode (kartu portofolio) --}}
        @if ($episodes->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                Belum ada episode yang tersedia untuk Anda.
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($episodes as $ep)
                    <button type="button" wire:click="pilihEpisode({{ $ep->id }})" wire:key="ep-{{ $ep->id }}"
                            class="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold text-slate-800 transition group-hover:text-brand-700">{{ $ep->name }}</h3>
                            <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $ep->status->color() }}">{{ $ep->status->label() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $ep->klien?->name ?? 'Tanpa klien' }}</p>
                        @if ($ep->description)<p class="mt-2 line-clamp-2 text-xs text-slate-500">{{ $ep->description }}</p>@endif
                        <div class="mt-4 flex items-center gap-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                            <span>{{ $ep->adegan_count }} adegan</span>
                            <span class="ml-auto font-medium text-brand-600">Buka →</span>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    @else
        {{-- Tracker tahap --}}
        @if ($proyek->isClosed())
            <div class="mb-3 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm text-slate-500">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                Episode <span class="font-semibold">selesai (closed)</span> — read-only. Buka kembali dari menu <span class="font-medium">Episode</span> untuk mengedit.
            </div>
        @endif
        @if ($tahapList->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                Belum ada tahap aktif untuk {{ $fase->label() }}. Atur di <a href="{{ route('pengaturan.pipeline') }}" wire:navigate class="text-brand-600 hover:underline">Konfigurasi Pipeline</a>.
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/70 text-[11px] uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3 text-left font-semibold">Tahap</th>
                                <th class="px-4 py-3 text-left font-semibold">Artis</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-left font-semibold">Jadwal</th>
                                <th class="px-4 py-3 text-left font-semibold">File</th>
                                <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($tahapList as $tahap)
                                @php $row = $rows->get($tahap->id); @endphp
                                <tr wire:key="row-{{ $tahap->id }}" class="transition hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800">{{ $tahap->name }}</div>
                                        @if ($row?->deskripsi)<div class="mt-0.5 line-clamp-1 text-xs text-slate-400">{{ $row->deskripsi }}</div>@endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $row?->artis?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ ($row?->status ?? \App\Enums\TaskStatus::NOT_STARTED)->color() }}">
                                            {{ ($row?->status ?? \App\Enums\TaskStatus::NOT_STARTED)->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500">
                                        @if ($row?->start_date || $row?->deadline)
                                            {{ $row->start_date?->locale('id')->isoFormat('D MMM Y') ?? '—' }}
                                            <span class="text-slate-300">→</span>
                                            {{ $row->deadline?->locale('id')->isoFormat('D MMM Y') ?? '—' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        @if ($row?->file_url)
                                            <a href="{{ $row->file_url }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline">buka ↗</a>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $st = $row?->status ?? \App\Enums\TaskStatus::NOT_STARTED;
                                            $milikSaya = $row && $row->artist_id === auth()->id();
                                            $bisaKerja = $row && ($milikSaya || $dapatReview) && $proyek->isPublished();
                                        @endphp
                                        <div class="flex items-center justify-center gap-1">
                                            @if ($bisaKerja && $st === \App\Enums\TaskStatus::NOT_STARTED)
                                                <button wire:click="mulai({{ $row->id }})" class="rounded-md bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">Mulai</button>
                                            @elseif ($bisaKerja && $st === \App\Enums\TaskStatus::IN_PROGRESS)
                                                <button x-on:click="$confirm(@js('Ajukan pekerjaan ini untuk direview?'), { confirmText: 'Ajukan', icon: 'info' }).then(ok => ok && $wire.propose({{ $row->id }}))" class="rounded-md bg-amber-500 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-600">Propose</button>
                                            @elseif ($dapatReview && $st === \App\Enums\TaskStatus::REVIEW)
                                                <button wire:click="setujui({{ $row->id }})" class="rounded-md bg-green-600 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-green-700">Setujui</button>
                                                <button wire:click="tolak({{ $row->id }})" class="rounded-md bg-red-600 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700">Tolak</button>
                                            @endif

                                            @if ($row)
                                                <button wire:click="bukaHistory({{ $row->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 transition hover:bg-slate-200" title="Riwayat">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                </button>
                                                <button wire:click="bukaDiskusi({{ $row->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 transition hover:bg-brand-100 hover:text-brand-700" title="Diskusi">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.9A7.96 7.96 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                                </button>
                                            @endif

                                            @if ($dapatKelola)
                                                <button wire:click="edit({{ $tahap->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 transition hover:bg-brand-100 hover:text-brand-700" title="Setup (assign & jadwal)">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- Modal edit tahap --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="cancel"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Edit Tahap</h2>
                    <button wire:click="cancel" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <form wire:submit="save" class="space-y-4 px-5 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Artis (PIC)</label>
                        <select wire:model="artistId" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">— belum ditugaskan —</option>
                            @foreach ($daftarArtis as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Status berjalan lewat alur kerja (Mulai → Propose → Setujui), bukan diatur manual.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal mulai</label>
                            <input type="date" wire:model="startDate" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Deadline</label>
                            <input type="date" wire:model="deadline" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('deadline') border-red-400 @enderror">
                            @error('deadline') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Estimasi (hari) <span class="text-slate-400">(untuk kapasitas)</span></label>
                        <input type="number" min="0" wire:model="estimasiHari" placeholder="mis. 3" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('estimasiHari') border-red-400 @enderror">
                        @error('estimasiHari') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tautan file/output <span class="text-slate-400">(opsional)</span></label>
                        <input type="url" wire:model="fileUrl" placeholder="https://… (tautan baru otomatis jadi versi berikutnya)" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('fileUrl') border-red-400 @enderror">
                        @error('fileUrl') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @if ($versiTahap->isNotEmpty())
                            <ul class="mt-2 space-y-1">
                                @foreach ($versiTahap as $v)
                                    <li wire:key="vt-{{ $v->id }}" class="flex items-center gap-2 rounded border border-slate-100 bg-slate-50 px-2 py-1 text-xs">
                                        <span class="shrink-0 rounded bg-brand-100 px-1.5 py-0.5 font-semibold text-brand-700">v{{ $v->version }}</span>
                                        <a href="{{ $v->url }}" target="_blank" rel="noopener" class="truncate text-brand-600 hover:underline">{{ $v->url }}</a>
                                        <span class="ml-auto shrink-0 text-[10px] text-slate-400">{{ $v->created_at?->locale('id')->diffForHumans() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <label class="block text-sm font-medium text-slate-700">Deskripsi / catatan <span class="text-slate-400">(opsional)</span></label>
                            @if ($aiAktif)
                                <button type="button" wire:click="isiDeskripsiAi" wire:loading.attr="disabled" wire:target="isiDeskripsiAi"
                                        class="inline-flex items-center gap-1 rounded-md bg-gold-100 px-2 py-1 text-[11px] font-semibold text-gold-600 transition hover:bg-gold-200 disabled:opacity-60">
                                    <svg wire:loading.remove wire:target="isiDeskripsiAi" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                    <svg wire:loading wire:target="isiDeskripsiAi" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                    Bantu tulis (AI)
                                </button>
                            @endif
                        </div>
                        <textarea wire:model="deskripsi" rows="3" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('deskripsi') border-red-400 @enderror"></textarea>
                        @error('deskripsi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="cancel" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Tolak (wajib alasan) --}}
    @if ($showReject)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="$set('showReject', false)"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-5 py-4"><h2 class="text-base font-semibold text-slate-900">Tolak / Minta Revisi</h2></div>
                <form wire:submit="konfirmasiTolak" class="space-y-4 px-5 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Alasan penolakan <span class="text-red-500">*</span></label>
                        <textarea wire:model="rejectAlasan" rows="3" placeholder="Jelaskan yang perlu diperbaiki…" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('rejectAlasan') border-red-400 @enderror"></textarea>
                        @error('rejectAlasan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="$set('showReject', false)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Tolak & Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Riwayat --}}
    @if ($showHistory)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="tutupHistory"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Riwayat Pekerjaan</h2>
                    <button wire:click="tutupHistory" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <div class="max-h-96 overflow-y-auto px-5 py-4">
                    @forelse ($historyItems as $a)
                        <div wire:key="akt-{{ $a->id }}" class="flex gap-3 border-b border-slate-100 py-2.5 last:border-0">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $a->kind === 'APPROVE' ? 'bg-green-500' : ($a->kind === 'REJECT' ? 'bg-red-500' : 'bg-brand-400') }}"></span>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-slate-700">{{ $a->label() }}</p>
                                @if ($a->note)<p class="mt-0.5 text-xs text-slate-500">{{ $a->note }}</p>@endif
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $a->author?->name ?? 'Sistem' }} · {{ $a->created_at?->locale('id')->isoFormat('D MMM Y HH:mm') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-xs text-slate-400">Belum ada aktivitas.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- Modal diskusi (komentar berulir) --}}
    @if ($showDiskusi && $diskusiId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="tutupDiskusi"></div>
            <div class="relative max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Diskusi tahap</h3>
                    <button wire:click="tutupDiskusi" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <livewire:komentar :subjek-type="App\Models\TugasTahap::class" :subjek-id="$diskusiId" :key="'kom-tahap-'.$diskusiId" />
            </div>
        </div>
    @endif
</div>
