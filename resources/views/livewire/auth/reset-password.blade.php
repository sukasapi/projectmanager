@php $perusahaan = \App\Models\Perusahaan::current(); @endphp
<div class="flex min-h-screen items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">
        <div class="mb-6 flex items-center justify-center gap-2">
            @if ($perusahaan->logoUrl())
                <img src="{{ $perusahaan->logoUrl() }}" alt="logo" class="h-9 w-9 rounded-lg object-cover">
            @else
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-400 font-bold text-brand-900">◑</span>
            @endif
            <span class="text-lg font-semibold text-slate-900">{{ $perusahaan->appName() }}</span>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h2 class="text-2xl font-bold text-slate-900">Atur ulang kata sandi</h2>
            <p class="mt-1 text-sm text-slate-500">Buat kata sandi baru untuk akun Anda.</p>

            <form wire:submit="aturUlang" class="mt-6 space-y-5">
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" id="email" wire:model="email" autocomplete="username"
                           class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('email') border-red-400 @enderror">
                    @error('email') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Kata sandi baru</label>
                    <input type="password" id="password" wire:model="password" autocomplete="new-password"
                           placeholder="Minimal 8 karakter"
                           class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('password') border-red-400 @enderror">
                    @error('password') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Ulangi kata sandi</label>
                    <input type="password" id="password_confirmation" wire:model="password_confirmation" autocomplete="new-password"
                           placeholder="••••••••"
                           class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="aturUlang"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-60">
                    <svg wire:loading wire:target="aturUlang" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="aturUlang">Simpan kata sandi baru</span>
                    <span wire:loading wire:target="aturUlang">Menyimpan…</span>
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm">
            <a href="{{ route('login') }}" wire:navigate class="text-brand-600 hover:underline">← Kembali ke halaman masuk</a>
        </p>
    </div>
</div>
