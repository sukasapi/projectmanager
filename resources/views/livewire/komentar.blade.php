<div>
    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Diskusi ({{ $komentar->count() }})</h3>

    <ul class="space-y-3">
        @forelse ($komentar as $k)
            <li wire:key="kom-{{ $k->id }}">
                <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span class="font-semibold text-slate-700">{{ $k->author?->name ?? 'Anonim' }}</span>
                        <span>{{ $k->created_at?->locale('id')->diffForHumans() }}</span>
                    </div>
                    <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $k->body }}</p>
                    <div class="mt-1 flex items-center gap-3 text-[11px]">
                        <button wire:click="balas({{ $k->id }})" class="font-medium text-brand-600 hover:underline">Balas</button>
                        @if ($k->author_id === auth()->id() || auth()->user()?->isSupervisory())
                            <button x-on:click="$confirm(@js('Hapus komentar ini?'), { danger: true }).then(ok => ok && $wire.hapus({{ $k->id }}))" class="text-red-500 hover:underline">Hapus</button>
                        @endif
                    </div>
                </div>

                {{-- Balasan --}}
                @if ($k->balasan->isNotEmpty())
                    <ul class="mt-2 space-y-2 border-l-2 border-slate-100 pl-3">
                        @foreach ($k->balasan as $b)
                            <li wire:key="kom-{{ $b->id }}" class="rounded-lg bg-white px-3 py-1.5">
                                <div class="flex items-center justify-between text-[11px] text-slate-500">
                                    <span class="font-semibold text-slate-700">{{ $b->author?->name ?? 'Anonim' }}</span>
                                    <span>{{ $b->created_at?->locale('id')->diffForHumans() }}</span>
                                </div>
                                <p class="mt-0.5 whitespace-pre-line text-sm text-slate-700">{{ $b->body }}</p>
                                @if ($b->author_id === auth()->id() || auth()->user()?->isSupervisory())
                                    <button x-on:click="$confirm(@js('Hapus balasan ini?'), { danger: true }).then(ok => ok && $wire.hapus({{ $b->id }}))" class="mt-0.5 text-[11px] text-red-500 hover:underline">Hapus</button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Kotak balas kontekstual --}}
                @if ($balasUntuk === $k->id)
                    <div class="mt-2 pl-3">
                        <textarea wire:model="isi" rows="2" placeholder="Tulis balasan…"
                                  class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        @error('isi') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="mt-1 flex gap-2">
                            <button wire:click="kirim" class="rounded-md bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-800">Kirim balasan</button>
                            <button wire:click="batalBalas" class="rounded-md px-2 py-1.5 text-xs text-slate-500 hover:bg-slate-100">Batal</button>
                        </div>
                    </div>
                @endif
            </li>
        @empty
            <li class="text-xs text-slate-400">Belum ada komentar. Mulai diskusi di bawah.</li>
        @endforelse
    </ul>

    {{-- Komentar baru (top-level) --}}
    @if ($balasUntuk === null)
        <div class="mt-3">
            <textarea wire:model="isi" rows="2" placeholder="Tulis komentar…"
                      class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
            @error('isi') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button wire:click="kirim" class="mt-1 rounded-md bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-800">Kirim</button>
        </div>
    @endif
</div>
