@php $perusahaan = \App\Models\Perusahaan::current(); @endphp
<div class="flex min-h-screen">
    {{-- Panel brand (kiri) --}}
    <div class="relative hidden w-1/2 flex-col overflow-hidden bg-brand-900 text-white lg:flex">
        @if ($perusahaan->loginImageUrl())
            <img src="{{ $perusahaan->loginImageUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover brightness-75">
            <div class="absolute inset-0 bg-gradient-to-b from-brand-950/35 via-brand-950/25 to-brand-950/80"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-brand-950/65 via-brand-950/20 to-transparent"></div>
        @endif
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-gold-400/20 blur-3xl"></div>

        <div class="relative flex min-h-screen w-full items-end justify-center overflow-hidden border-r border-white/10 text-left shadow-2xl"
             style="padding: 2.5rem 2.5rem 6rem;">
            <div class="relative w-full max-w-2xl rounded-2xl border border-white/25 shadow-2xl ring-1 ring-black/30"
                 style="background-color: rgba(8, 18, 28, 0.78); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); padding: 2.25rem 2.75rem 2.5rem;">
                <h1 class="max-w-lg text-3xl font-bold leading-tight text-white drop-shadow-lg xl:text-4xl">
                    Kelola pipeline<br>produksi animasi<br>
                    <span class="text-gold-300">dalam satu tempat.</span>
                </h1>
                <p class="mt-6 max-w-xl text-sm leading-6 text-white/95 xl:text-base xl:leading-7">
                    Lacak shot, aset, dan revisi dari pra-produksi hingga mastering — terstruktur
                    seperti pipeline studio nyata.
                </p>
                <ul class="mt-6 space-y-3 rounded-xl text-sm font-semibold text-white/95">
                    <li class="flex items-center gap-2"><span class="text-gold-300">✓</span> Matriks shot gaya spreadsheet</li>
                    <li class="flex items-center gap-2"><span class="text-gold-300">✓</span> Kalkulasi durasi otomatis</li>
                    <li class="flex items-center gap-2"><span class="text-gold-300">✓</span> Review &amp; revisi berriwayat</li>
                </ul>
            </div>

            <p class="absolute left-1/2 -translate-x-1/2 rounded-full bg-brand-950/70 px-3 py-1 text-xs text-white/80 backdrop-blur-[4px]" style="bottom: 1.75rem;">{{ $perusahaan->footer() }}</p>
        </div>
    </div>

    {{-- Form login (kanan) --}}
    <div class="flex w-full items-center justify-center bg-slate-100 p-6 lg:w-1/2">
        <div class="w-full max-w-md">
            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="mb-8 flex flex-col items-center gap-3 text-center">
                    @if ($perusahaan->logoUrl())
                        <img src="{{ $perusahaan->logoUrl() }}" alt="logo" class="h-12 w-12 rounded-xl border border-slate-200 object-cover shadow-sm">
                    @else
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-400 text-xl font-bold text-brand-900 shadow-sm">◑</span>
                    @endif
                    <div class="leading-tight">
                        <span class="block text-xl font-bold tracking-tight text-slate-900">{{ $perusahaan->appName() }}</span>
                        <span class="mt-1 block text-sm text-slate-500">{{ $perusahaan->name }}</span>
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-slate-900">Selamat datang kembali</h2>
                <p class="mt-1 text-sm text-slate-500">Masuk untuk melanjutkan ke dashboard produksi.</p>

                @if (session('status'))
                    <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
                @endif

                <form wire:submit="login" class="mt-6 space-y-5">
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" id="email" wire:model="email" autocomplete="username" autofocus
                               placeholder="nama@studio.test"
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('email') border-red-400 @enderror">
                        @error('email') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi</label>
                            <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-medium text-brand-600 hover:underline">Lupa kata sandi?</a>
                        </div>
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
