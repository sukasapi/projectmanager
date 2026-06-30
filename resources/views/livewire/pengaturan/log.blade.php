<div class="space-y-5">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Log Aplikasi & AI</h1>
        <p class="text-sm text-slate-500">Audit log sistem dan pemanggilan AI (Gemini).</p>
    </div>

    {{-- Tab --}}
    <div class="flex gap-1 rounded-lg bg-slate-100 p-1 text-sm">
        <button wire:click="tab('app')" class="flex-1 rounded-md px-3 py-1.5 font-medium transition {{ $tab === 'app' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">Log Aplikasi</button>
        <button wire:click="tab('ai')" class="flex-1 rounded-md px-3 py-1.5 font-medium transition {{ $tab === 'ai' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">Log AI</button>
    </div>

    @if ($tab === 'app')
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-900 shadow-sm">
            <div class="border-b border-white/10 px-4 py-2 text-xs font-medium text-slate-300">storage/logs/laravel.log (60KB terakhir)</div>
            <pre class="max-h-[32rem] overflow-auto p-4 text-[11px] leading-relaxed text-slate-200">{{ $appLog }}</pre>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/70 text-[11px] uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3 text-left font-semibold">Waktu</th>
                            <th class="px-4 py-3 text-left font-semibold">Pengguna</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-left font-semibold">Prompt</th>
                            <th class="px-4 py-3 text-left font-semibold">Hasil / Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($aiLog as $l)
                            <tr wire:key="ai-{{ $l->id }}" class="align-top hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-xs text-slate-500">{{ $l->created_at?->timezone($tz)->isoFormat('D MMM Y HH:mm') }}</td>
                                <td class="px-4 py-2.5 text-xs text-slate-600">{{ $l->pengguna?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $l->status === 'ok' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $l->status }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-600"><div class="max-w-xs line-clamp-2">{{ $l->prompt }}</div></td>
                                <td class="px-4 py-2.5 text-xs text-slate-600"><div class="max-w-sm line-clamp-2">{{ $l->error ?? $l->response }}</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-xs text-slate-400">Belum ada pemanggilan AI.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
