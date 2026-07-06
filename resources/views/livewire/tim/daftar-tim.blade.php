<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Tim & Artis</h1>
            <p class="text-sm text-slate-500">Direktori artis terdaftar beserta jenis kepegawaian & beban tugas.</p>
        </div>
        @if ($bisaKelola)
            <button wire:click="create"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Artis
            </button>
        @endif
    </div>

    {{-- Toolbar filter --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M11 19a8 8 0 100-16 8 8 0 000 16z" />
            </svg>
            <input type="search" wire:model.live.debounce.300ms="cari" placeholder="Cari nama / email / peran…"
                   class="w-64 rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
        <select wire:model.live="filterTipe" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            <option value="">Semua tipe</option>
            @foreach ($daftarTipe as $t)
                <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
        </select>
    </div>

    @if ($anggota->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            Tidak ada anggota yang cocok.
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500">
                            <th class="px-4 py-2.5 text-left">Artis</th>
                            <th class="px-4 py-2.5 text-left">Peran / Jabatan</th>
                            <th class="px-4 py-2.5 text-left">Kepegawaian</th>
                            <th class="px-4 py-2.5 text-right">Tugas aktif</th>
                            <th class="px-4 py-2.5 text-center">Status</th>
                            <th class="px-4 py-2.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($anggota as $u)
                            @php $totalTugas = $u->tugas_shot_count + $u->tugas_praproduksi_count + $u->aset_count + $u->tugas_pascaproduksi_count; @endphp
                            <tr wire:key="tim-{{ $u->id }}" class="transition hover:bg-slate-50 {{ $u->is_active ? '' : 'opacity-60' }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                        <div class="leading-tight">
                                            <div class="font-semibold text-slate-800">{{ $u->name }}</div>
                                            <div class="text-xs text-slate-400">{{ $u->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-700">{{ $u->role ?? '—' }}</div>
                                    @if ($u->jabatan)<div class="text-xs text-slate-400">{{ $u->jabatan }}</div>@endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $u->employment_type?->label() }}</span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ $totalTugas }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($u->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('tim.detail', $u->id) }}" wire:navigate
                                           class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 transition hover:bg-brand-100 hover:text-brand-700" title="Detail / beban kerja">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </a>
                                        @if ($bisaKelola && auth()->user()->bolehMenetapkanPeran($u->role))
                                            <button wire:click="edit({{ $u->id }})" class="inline-flex rounded-md bg-slate-100 p-1.5 text-slate-500 transition hover:bg-brand-100 hover:text-brand-700" title="Edit">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button x-on:click="$confirm(@js(($u->is_active ? 'Nonaktifkan ' : 'Aktifkan kembali ').$u->name.'?')).then(ok => ok && $wire.toggleAktif({{ $u->id }}))"
                                                    class="inline-flex rounded-md bg-slate-100 p-1.5 text-amber-600 transition hover:bg-amber-100 hover:text-amber-700" title="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 11-12.728 0M12 3v9" /></svg>
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

    {{-- Modal form --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-brand-950/60" wire:click="cancel"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">{{ $editingId ? 'Edit Artis' : 'Tambah Artis' }}</h2>
                    <button wire:click="cancel" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <form wire:submit="save" class="space-y-4 px-5 py-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
                            <input type="text" wire:model="name" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror">
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Peran <span class="text-slate-400">(hak akses)</span></label>
                            <select wire:model="role" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('role') border-red-400 @enderror">
                                @foreach ($peranOpsi as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                            </select>
                            @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Jabatan <span class="text-slate-400">(spesialisasi — opsional)</span></label>
                        <input type="text" wire:model="jabatan" placeholder="Animator, Modeller, SLRC…" list="jabatan-opsi" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('jabatan') border-red-400 @enderror">
                        <datalist id="jabatan-opsi">@foreach ($jabatanOpsi as $j)<option>{{ $j }}</option>@endforeach</datalist>
                        <p class="mt-1 text-[11px] text-slate-400">Seorang Artis dapat dipromosikan menjadi Team Lead lewat kolom Peran; jabatannya tetap.</p>
                        @error('jabatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" wire:model="email" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('email') border-red-400 @enderror">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Jenis kepegawaian</label>
                            <select wire:model="employmentType" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @foreach ($daftarTipe as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </select>
                            <div class="mt-2">
                                <label class="mb-1 block text-xs font-medium text-slate-600">Kapasitas (hari kerja/minggu)</label>
                                <input type="number" min="0" max="7" wire:model="kapasitasHari" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('kapasitasHari') border-red-400 @enderror">
                                @error('kapasitasHari') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Kata sandi {{ $editingId ? '(kosongkan jika tetap)' : '' }}</label>
                            <input type="password" wire:model="password" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('password') border-red-400 @enderror">
                            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    {{-- Kontak --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Telepon</label>
                            <input type="text" wire:model="phone" placeholder="0812…" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('phone') border-red-400 @enderror">
                            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">WhatsApp</label>
                            <input type="text" wire:model="whatsapp" placeholder="62812…" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('whatsapp') border-red-400 @enderror">
                            @error('whatsapp') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
                        <textarea wire:model="address" rows="2" placeholder="Alamat domisili" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Latitude <span class="text-slate-400">(opsional)</span></label>
                            <input type="text" wire:model="latitude" placeholder="-7.7956" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('latitude') border-red-400 @enderror">
                            @error('latitude') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Longitude <span class="text-slate-400">(opsional)</span></label>
                            <input type="text" wire:model="longitude" placeholder="110.3695" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('longitude') border-red-400 @enderror">
                            @error('longitude') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <p class="col-span-2 -mt-1 text-[11px] text-slate-400">Pemilihan titik di peta interaktif menyusul; untuk kini isi koordinat manual bila perlu.</p>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Akun aktif
                    </label>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="cancel" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60">
                            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            {{ $editingId ? 'Simpan Perubahan' : 'Simpan Artis' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
