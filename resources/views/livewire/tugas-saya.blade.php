<div class="mx-auto max-w-4xl px-4 py-6">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Tugas Saya</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $total }} pekerjaan aktif yang ditugaskan kepada Anda (belum disetujui), diurutkan dari deadline terdekat.
        </p>
    </div>

    @forelse ($grup as $episode => $items)
        <div class="mb-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" wire:key="ep-{{ $loop->index }}">
            <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-2.5 text-sm font-semibold text-slate-700">{{ $episode }}</div>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    @foreach ($items as $i)
                        @php $overdue = $i['deadline'] && $i['deadline']->isBefore(today()); @endphp
                        <tr wire:key="t-{{ $loop->parent->index }}-{{ $loop->index }}" class="transition hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $i['label'] }}</div>
                                <span class="text-[11px] text-slate-400">{{ $i['kind'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded px-2 py-0.5 text-[11px] font-medium {{ $i['status']->color() }}">{{ $i['status']->label() }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs {{ $overdue ? 'font-semibold text-red-600' : 'text-slate-500' }}">
                                @if ($i['deadline'])
                                    {{ $i['deadline']->locale('id')->isoFormat('D MMM Y') }}
                                    @if ($overdue) · lewat @endif
                                @else
                                    <span class="text-slate-300">— tanpa deadline</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ $i['url'] }}" wire:navigate
                                   class="inline-flex items-center gap-1 rounded-md bg-brand-700 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-800">
                                    Buka
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            🎉 Tidak ada tugas aktif. Semua pekerjaan Anda sudah selesai atau belum ada penugasan.
        </div>
    @endforelse
</div>
