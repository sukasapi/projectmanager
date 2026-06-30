<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e293b; font-size: 11px; margin: 0; }
        .cover { background: #16313f; color: #fff; padding: 28px 32px; }
        .cover .brand { font-size: 20px; font-weight: bold; color: #d4a82c; }
        .cover .tagline { font-size: 11px; color: #7ba7bc; margin-top: 2px; }
        .cover .title { font-size: 24px; font-weight: bold; margin-top: 22px; }
        .cover .sub { font-size: 12px; color: #cbd5e1; margin-top: 4px; }
        .content { padding: 18px 32px; }
        h2 { font-size: 14px; color: #2b4f62; border-bottom: 2px solid #d4a82c; padding-bottom: 4px; margin: 22px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th { background: #eef4f7; color: #475569; text-align: left; font-size: 10px; text-transform: uppercase; padding: 6px 8px; border-bottom: 1px solid #cbd5e1; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .meta td { border: none; padding: 2px 0; }
        .meta .k { color: #64748b; width: 130px; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 9px; font-weight: bold; }
        .b-green { background: #dcfce7; color: #15803d; }
        .b-blue { background: #dbeafe; color: #1d4ed8; }
        .b-amber { background: #fef3c7; color: #b45309; }
        .b-gray { background: #f1f5f9; color: #475569; }
        .scene { background: #eef4f7; color: #16313f; font-weight: bold; }
        .muted { color: #94a3b8; }
        .footer { position: fixed; bottom: 12px; left: 32px; right: 32px; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 4px; }
    </style>
    @php
        $badge = function ($status) {
            if (! $status) return '<span class="badge b-gray">—</span>';
            $map = ['APPROVED' => 'b-green', 'IN_PROGRESS' => 'b-blue', 'REVIEW' => 'b-amber', 'NOT_STARTED' => 'b-gray'];
            return '<span class="badge '.($map[$status->value] ?? 'b-gray').'">'.$status->label().'</span>';
        };
    @endphp
</head>
<body>
    <div class="cover">
        <div class="brand">{{ $perusahaan->name }}</div>
        @if ($perusahaan->tagline)<div class="tagline">{{ $perusahaan->tagline }}</div>@endif
        <div class="title">Production Bible</div>
        <div class="sub">{{ $proyek->name }}@if ($proyek->klien) · {{ $proyek->klien->name }}@endif</div>
    </div>

    <div class="content">
        <h2>Informasi Episode</h2>
        <table class="meta">
            <tr><td class="k">Episode</td><td>{{ $proyek->name }}</td></tr>
            <tr><td class="k">Klien</td><td>{{ $proyek->klien->name ?? '—' }}</td></tr>
            <tr><td class="k">Status</td><td>{{ $proyek->status?->label() ?? '—' }}</td></tr>
            <tr><td class="k">Total Durasi</td><td>{{ (int) $proyek->adegan->sum('total_duration') }} detik · {{ $proyek->adegan->count() }} adegan</td></tr>
            @if ($proyek->description)<tr><td class="k">Deskripsi</td><td>{{ $proyek->description }}</td></tr>@endif
        </table>

        {{-- Pra-Produksi --}}
        <h2>Pra-Produksi</h2>
        @if ($pra->isEmpty())
            <p class="muted">Belum ada data pra-produksi.</p>
        @else
            <table>
                <thead><tr><th>Tahap</th><th>Artis (PIC)</th><th>Status</th><th>Deadline</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    @foreach ($pra as $t)
                        <tr>
                            <td><strong>{{ $t->tahap?->name }}</strong></td>
                            <td>{{ $t->artis?->name ?? '—' }}</td>
                            <td>{!! $badge($t->status) !!}</td>
                            <td>{{ $t->deadline?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td>{{ $t->deskripsi ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Asset Library --}}
        <h2>Asset Library</h2>
        @if ($proyek->aset->isEmpty())
            <p class="muted">Belum ada aset.</p>
        @else
            <table>
                <thead><tr><th>Aset</th><th>Tipe</th><th>Sub-tugas</th><th>Artis</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($proyek->aset as $a)
                        <tr>
                            <td>{{ $a->name }}</td>
                            <td>{{ $a->type?->label() ?? $a->type }}</td>
                            <td>{{ $a->task?->label() ?? $a->task }}</td>
                            <td>{{ $a->artist?->name ?? '—' }}</td>
                            <td>{!! $badge($a->status) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Produksi: breakdown shot per tahap --}}
        <h2>Produksi — Breakdown Shot</h2>
        @if ($proyek->adegan->isEmpty())
            <p class="muted">Belum ada adegan/shot.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Shot</th><th>Durasi</th>
                        @foreach ($tahapShot as $th)<th>{{ $th->name }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($proyek->adegan as $adegan)
                        <tr class="scene"><td colspan="{{ 2 + $tahapShot->count() }}">{{ $adegan->scene_name }} — {{ $adegan->total_duration }} dtk</td></tr>
                        @forelse ($adegan->shot as $shot)
                            <tr>
                                <td>
                                    {{ $shot->shot_code }}
                                    @if ($shot->description)<br><span class="muted">{{ $shot->description }}</span>@endif
                                </td>
                                <td>{{ $shot->duration_seconds }}s</td>
                                @foreach ($tahapShot as $th)
                                    @php $tg = $shot->tugasShot->firstWhere('tahap_id', $th->id); @endphp
                                    <td>
                                        {!! $badge($tg?->status) !!}
                                        @if ($tg && $tg->artists->isNotEmpty())<br><span class="muted">{{ $tg->artists->pluck('name')->join(', ') }}</span>@endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ 2 + $tahapShot->count() }}" class="muted">Belum ada shot.</td></tr>
                        @endforelse
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Pasca-Produksi --}}
        <h2>Pasca-Produksi</h2>
        @if ($pasca->isEmpty())
            <p class="muted">Belum ada data pasca-produksi.</p>
        @else
            <table>
                <thead><tr><th>Tahap</th><th>Artis (PIC)</th><th>Status</th><th>Deadline</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    @foreach ($pasca as $t)
                        <tr>
                            <td><strong>{{ $t->tahap?->name }}</strong></td>
                            <td>{{ $t->artis?->name ?? '—' }}</td>
                            <td>{!! $badge($t->status) !!}</td>
                            <td>{{ $t->deadline?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td>{{ $t->deskripsi ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        {{ $perusahaan->name }} · Production Bible "{{ $proyek->name }}" · dicetak {{ $dicetak }} WIB
    </div>
</body>
</html>
