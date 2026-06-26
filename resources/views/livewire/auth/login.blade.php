<div class="flex min-h-screen">
    {{-- Panel brand (kiri) --}}
    <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-brand-900 p-12 text-white lg:flex">
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-gold-400/20 blur-3xl"></div>

        <div class="relative flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-400 text-xl font-bold text-brand-900">◑</span>
            <div class="leading-tight">
                <span class="block text-xl font-semibold tracking-tight">AnimTrack</span>
                <span class="block text-xs text-brand-300">Owlorix Creative Lab</span>
            </div>
        </div>

        <div class="relative space-y-6">
            <h1 class="text-4xl font-bold leading-tight">
                Kelola pipeline<br>produksi animasi<br>
                <span class="text-gold-300">dalam satu tempat.</span>
            </h1>
            <p class="max-w-md text-brand-200">
                Lacak shot, aset, dan revisi dari pra-produksi hingga mastering — terstruktur
                seperti pipeline studio nyata.
            </p>
            <ul class="space-y-2 text-sm text-brand-200">
                <li class="flex items-center gap-2"><span class="text-gold-400">✓</span> Matriks shot gaya spreadsheet</li>
                <li class="flex items-center gap-2"><span class="text-gold-400">✓</span> Kalkulasi durasi otomatis</li>
                <li class="flex items-center gap-2"><span class="text-gold-400">✓</span> Review &amp; revisi berriwayat</li>
            </ul>
        </div>

        <p class="relative text-xs text-brand-400">© {{ date('Y') }} Owlorix Creative Lab.</p>
    </div>

    {{-- Form login (kanan) --}}
    <div class="flex w-full items-center justify-center bg-slate-100 p-6 lg:w-1/2">
        <div class="w-full max-w-md">
            <div class="mb-8 flex items-center gap-2 lg:hidden">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-400 font-bold text-brand-900">◑</span>
                <span class="text-lg font-semibold text-slate-900">AnimTrack</span>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <h2 class="text-2xl font-bold text-slate-900">Selamat datang kembali</h2>
                <p class="mt-1 text-sm text-slate-500">Masuk untuk melanjutkan ke dashboard produksi.</p>

                <form wire:submit="login" class="mt-6 space-y-5">
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" id="email" wire:model="email" autocomplete="username" autofocus
                               placeholder="nama@studio.test"
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('email') border-red-400 @enderror">
                        @error('email') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Kata Sandi</label>
                        <input type="password" id="password" wire:model="password" autocomplete="current-password"
                               placeholder="••••••••"
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('password') border-red-400 @enderror">
                        @error('password') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:model="remember"
                               class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Ingat saya
                    </label>

                    <button type="submit" wire:loading.attr="disabled"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-60">
                        <svg wire:loading wire:target="login" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="login">Masuk</span>
                        <span wire:loading wire:target="login">Memproses…</span>
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">
                Akun dikelola oleh administrator studio. Hubungi admin bila lupa kata sandi.
            </p>
        </div>
    </div>
</div>
