<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Pengaturan</h1>
        <p class="text-sm text-slate-500">Kelola akun Anda dan lihat kebijakan workspace.</p>
    </div>

    {{-- Profil akun --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-slate-700">Profil Akun</h2>
        </div>
        <form wire:submit="simpanProfil" class="space-y-4 px-5 py-5">
            @if (session()->has('status') || $errors->isEmpty())
                <div x-data="{ show: false }" x-on:profil-tersimpan.window="show = true; setTimeout(() => show = false, 2500)" x-show="show" x-cloak class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">Profil disimpan.</div>
            @endif
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
                    <input type="text" wire:model="name" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" wire:model="email" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('email') border-red-400 @enderror">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 text-xs text-slate-400">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{{ $user->employment_type?->label() }}</span>
                <span>Peran: {{ $user->role ?? '—' }}</span>
                <span class="text-slate-300">(diatur oleh supervisor)</span>
            </div>
            <div class="flex justify-end border-t border-slate-100 pt-4">
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60">Simpan Profil</button>
            </div>
        </form>
    </div>

    {{-- Ganti kata sandi --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="text-sm font-semibold text-slate-700">Ganti Kata Sandi</h2></div>
        <form wire:submit="ubahPassword" class="space-y-4 px-5 py-5">
            <div x-data="{ show: false }" x-on:password-tersimpan.window="show = true; setTimeout(() => show = false, 2500)" x-show="show" x-cloak class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">Kata sandi diperbarui.</div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kata sandi saat ini</label>
                <input type="password" wire:model="currentPassword" autocomplete="current-password" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('currentPassword') border-red-400 @enderror">
                @error('currentPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kata sandi baru</label>
                    <input type="password" wire:model="newPassword" autocomplete="new-password" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('newPassword') border-red-400 @enderror">
                    @error('newPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Konfirmasi kata sandi</label>
                    <input type="password" wire:model="newPassword_confirmation" autocomplete="new-password" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
            </div>
            <div class="flex justify-end border-t border-slate-100 pt-4">
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60">Perbarui Kata Sandi</button>
            </div>
        </form>
    </div>

    {{-- Kebijakan kehadiran (read-only) --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="text-sm font-semibold text-slate-700">Kebijakan Kehadiran</h2></div>
        <dl class="grid gap-x-6 gap-y-3 px-5 py-5 text-sm sm:grid-cols-2">
            <div class="flex justify-between border-b border-slate-50 pb-2"><dt class="text-slate-500">Jam kerja</dt><dd class="font-medium text-slate-700">{{ $kebijakan['jam_masuk'] }}–{{ $kebijakan['jam_pulang'] }} WIB</dd></div>
            <div class="flex justify-between border-b border-slate-50 pb-2"><dt class="text-slate-500">Toleransi terlambat</dt><dd class="font-medium text-slate-700">{{ $kebijakan['toleransi'] }} menit</dd></div>
            <div class="flex justify-between border-b border-slate-50 pb-2"><dt class="text-slate-500">Zona waktu</dt><dd class="font-medium text-slate-700">{{ $kebijakan['timezone'] }}</dd></div>
            <div class="flex justify-between border-b border-slate-50 pb-2"><dt class="text-slate-500">Geotag offsite</dt><dd class="font-medium text-slate-700">{{ $kebijakan['geotag'] ? 'Aktif' : 'Nonaktif (IP saja)' }}</dd></div>
        </dl>
        <p class="px-5 pb-4 text-[11px] text-slate-400">Diatur Super Admin di Profil Perusahaan. Freelance: jam masuk tidak ditegakkan. Lupa clock-out dianggap pulang tepat waktu ({{ $kebijakan['jam_pulang'] }}).</p>
    </div>

    {{-- Profil perusahaan + kebijakan (Super Admin) --}}
    @if ($isSuperAdmin)
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-3.5"><h2 class="text-sm font-semibold text-slate-700">Profil Perusahaan / Studio</h2></div>
            <form wire:submit="simpanPerusahaan" class="space-y-4 px-5 py-5">
                <div x-data="{ show: false }" x-on:perusahaan-tersimpan.window="show = true; setTimeout(() => show = false, 2500)" x-show="show" x-cloak class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">Profil perusahaan disimpan.</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nama studio</label>
                        <input type="text" wire:model="comp_name" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('comp_name') border-red-400 @enderror">
                        @error('comp_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nama legal/PT</label>
                        <input type="text" wire:model="comp_legal_name" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tagline</label>
                    <input type="text" wire:model="comp_tagline" placeholder="Creative Lab" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" wire:model="comp_email" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('comp_email') border-red-400 @enderror">
                        @error('comp_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Telepon</label>
                        <input type="text" wire:model="comp_phone" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Website</label>
                        <input type="url" wire:model="comp_website" placeholder="https://…" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('comp_website') border-red-400 @enderror">
                        @error('comp_website') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
                    <textarea wire:model="comp_address" rows="2" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                </div>

                {{-- Kebijakan kehadiran --}}
                <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Kebijakan Kehadiran</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Jam masuk</label>
                            <input type="time" wire:model="comp_jam_masuk" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('comp_jam_masuk') border-red-400 @enderror">
                            @error('comp_jam_masuk') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Jam pulang</label>
                            <input type="time" wire:model="comp_jam_pulang" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('comp_jam_pulang') border-red-400 @enderror">
                            @error('comp_jam_pulang') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Toleransi (menit)</label>
                            <input type="number" min="0" max="240" wire:model="comp_toleransi" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('comp_toleransi') border-red-400 @enderror">
                            @error('comp_toleransi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-400">Karyawan yang lupa clock-out otomatis dianggap pulang tepat waktu pada jam pulang.</p>
                </div>

                {{-- Tampilan aplikasi --}}
                <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Tampilan Aplikasi</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Nama aplikasi</label>
                            <input type="text" wire:model="comp_app_name" placeholder="AnimTrack" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('comp_app_name') border-red-400 @enderror">
                            @error('comp_app_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Teks footer</label>
                            <input type="text" wire:model="comp_footer" placeholder="© {{ date('Y') }} Studio Anda" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Logo</label>
                            <div class="flex items-center gap-3">
                                @if ($perusahaan->logoUrl())<img src="{{ $perusahaan->logoUrl() }}" alt="logo" class="h-10 w-10 rounded-lg object-cover">@endif
                                <input type="file" wire:model="logoFile" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-brand-700">
                            </div>
                            <div wire:loading wire:target="logoFile" class="mt-1 text-[11px] text-slate-400">Mengunggah…</div>
                            @error('logoFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Gambar halaman login</label>
                            <div class="flex items-center gap-3">
                                @if ($perusahaan->loginImageUrl())<img src="{{ $perusahaan->loginImageUrl() }}" alt="login" class="h-10 w-16 rounded-lg object-cover">@endif
                                <input type="file" wire:model="loginFile" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-brand-700">
                            </div>
                            <div wire:loading wire:target="loginFile" class="mt-1 text-[11px] text-slate-400">Mengunggah…</div>
                            @error('loginFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="mt-3 text-[11px] text-slate-400">
                        Lihat juga <a href="{{ route('pengaturan.log') }}" wire:navigate class="text-brand-600 hover:underline">Log Aplikasi & AI</a>.
                    </p>
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-4">
                    <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60">Simpan Profil Perusahaan</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Pintasan admin --}}
    @if ($isAdmin)
        <div class="rounded-xl border border-brand-200 bg-brand-50/40 p-5">
            <h2 class="text-sm font-semibold text-brand-800">Administrasi</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($isSuperAdmin)
                    <a href="{{ route('pengaturan.pipeline') }}" wire:navigate class="rounded-lg border border-gold-400 bg-gold-50 px-3 py-1.5 text-sm font-medium text-gold-600 hover:bg-gold-100">⚙ Konfigurasi Pipeline</a>
                    <a href="{{ route('tim') }}" wire:navigate class="rounded-lg border border-brand-300 bg-white px-3 py-1.5 text-sm font-medium text-brand-700 hover:bg-brand-50">Kelola Tim & Artis</a>
                @endif
                <a href="{{ route('monitoring') }}" wire:navigate class="rounded-lg border border-brand-300 bg-white px-3 py-1.5 text-sm font-medium text-brand-700 hover:bg-brand-50">Monitoring</a>
                <a href="{{ route('laporan-kehadiran') }}" wire:navigate class="rounded-lg border border-brand-300 bg-white px-3 py-1.5 text-sm font-medium text-brand-700 hover:bg-brand-50">Laporan Kehadiran</a>
            </div>
        </div>
    @endif
</div>
