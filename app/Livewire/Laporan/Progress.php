<?php

namespace App\Livewire\Laporan;

use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use App\Enums\TaskStatus;
use App\Models\Adegan;
use App\Models\AktivitasTahap;
use App\Models\Proyek;
use App\Models\RevisiShot;
use App\Models\Shot;
use App\Models\Tahap;
use App\Models\TugasShot;
use App\Models\TugasTahap;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Laporan progress per episode: persentase penyelesaian per fase (Pra/Produksi/Pasca).
 * Pemantauan (Supervisor/Super Admin/Team Lead) melihat semua episode (studio-wide);
 * lainnya hanya yang ditugaskan padanya.
 */
#[Layout('components.layouts.app')]
class Progress extends Component
{
    public function mount(): void
    {
        // Laporan produksi = Pemantauan (Supervisor/Super Admin/Team Lead).
        abort_unless(Gate::allows('monitor-kehadiran'), 403);
    }

    public function render()
    {
        $supervisor = Gate::allows('monitor-kehadiran');

        $episodes = Proyek::query()
            ->when(! $supervisor, fn ($q) => $q->untukUser(auth()->id() ?? 0))
            ->with('klien')
            ->withSum('adegan as total_durasi', 'total_duration')
            ->orderByDesc('id')
            ->get();

        $epIds = $episodes->pluck('id');

        // Peta shot → episode + durasi (agregasi Produksi & bobot durasi; hemat query).
        $sceneToEp = Adegan::whereIn('project_id', $epIds)->pluck('project_id', 'id');
        $shots = Shot::whereIn('scene_id', $sceneToEp->keys())->get(['id', 'scene_id', 'duration_seconds']);
        $shotIds = $shots->pluck('id');
        $shotToEp = $shots->pluck('scene_id', 'id')->map(fn ($sceneId) => $sceneToEp[$sceneId] ?? null);

        // Produksi: total & selesai per episode + per-shot (untuk bobot durasi) — 1 query.
        $prodTotal = [];
        $prodDone = [];
        $shotTot = [];
        $shotApp = [];
        foreach (TugasShot::whereIn('shot_id', $shotIds)->get(['shot_id', 'status']) as $t) {
            $ep = $shotToEp[$t->shot_id] ?? null;
            if (! $ep) {
                continue;
            }
            $prodTotal[$ep] = ($prodTotal[$ep] ?? 0) + 1;
            $shotTot[$t->shot_id] = ($shotTot[$t->shot_id] ?? 0) + 1;
            if ($t->status === TaskStatus::APPROVED) {
                $prodDone[$ep] = ($prodDone[$ep] ?? 0) + 1;
                $shotApp[$t->shot_id] = ($shotApp[$t->shot_id] ?? 0) + 1;
            }
        }

        // Bobot durasi per episode: durasi tiap shot × fraksi sub-task yang disetujui.
        $durTotal = [];
        $durDone = [];
        foreach ($shots as $s) {
            $ep = $shotToEp[$s->id] ?? null;
            $tot = $shotTot[$s->id] ?? 0;
            if (! $ep || $tot === 0) {
                continue;
            }
            $frac = ($shotApp[$s->id] ?? 0) / $tot;
            $durTotal[$ep] = ($durTotal[$ep] ?? 0) + $s->duration_seconds;
            $durDone[$ep] = ($durDone[$ep] ?? 0) + $s->duration_seconds * $frac;
        }

        // Pra/Pasca: total (snapshot aktif EPISODE) & selesai per episode+fase (2 query).
        $tahapTotal = [];
        foreach (Tahap::whereIn('project_id', $epIds)->aktif()->where('level', LevelTahap::EPISODE->value)->get(['project_id', 'phase']) as $th) {
            $tahapTotal[$th->project_id][$th->phase->value] = ($tahapTotal[$th->project_id][$th->phase->value] ?? 0) + 1;
        }
        $tahapDone = [];
        foreach (TugasTahap::whereIn('project_id', $epIds)->where('status', TaskStatus::APPROVED->value)->with('tahap:id,phase')->get() as $tt) {
            $ph = $tt->tahap?->phase?->value;
            if ($ph) {
                $tahapDone[$tt->project_id][$ph] = ($tahapDone[$tt->project_id][$ph] ?? 0) + 1;
            }
        }

        $data = $episodes->map(function (Proyek $ep) use ($prodTotal, $prodDone, $tahapTotal, $tahapDone, $durTotal, $durDone) {
            $praTotal = $tahapTotal[$ep->id][FaseProduksi::PRA->value] ?? 0;
            $pascaTotal = $tahapTotal[$ep->id][FaseProduksi::PASCA->value] ?? 0;
            $praDone = $tahapDone[$ep->id][FaseProduksi::PRA->value] ?? 0;
            $pascaDone = $tahapDone[$ep->id][FaseProduksi::PASCA->value] ?? 0;
            $pTotal = $prodTotal[$ep->id] ?? 0;
            $pDone = $prodDone[$ep->id] ?? 0;

            $fase = [
                ['label' => 'Pra-Produksi', 'done' => $praDone, 'total' => $praTotal],
                ['label' => 'Produksi', 'done' => $pDone, 'total' => $pTotal],
                ['label' => 'Pasca-Produksi', 'done' => $pascaDone, 'total' => $pascaTotal],
            ];

            $totDone = $praDone + $pDone + $pascaDone;
            $totAll = $praTotal + $pTotal + $pascaTotal;
            $dt = $durTotal[$ep->id] ?? 0;

            return [
                'ep' => $ep,
                'fase' => $fase,
                'persen' => $totAll > 0 ? (int) round($totDone / $totAll * 100) : 0,
                'persenDurasi' => $dt > 0 ? (int) round(($durDone[$ep->id] ?? 0) / $dt * 100) : null,
            ];
        });

        return view('livewire.laporan.progress', [
            'data' => $data,
            'overdue' => $this->overdue($epIds, $shotIds),
            'beban' => $this->beban($epIds, $shotIds),
            'funnel' => $this->funnel($shotIds),
            'velocity' => $this->velocity($epIds, $shotIds),
        ]);
    }

    /** Unduh ringkasan progress episode sebagai CSV (stream, cPanel-safe). */
    public function unduhCsv()
    {
        abort_unless(Gate::allows('monitor-kehadiran'), 403);

        $rows = $this->render()->getData()['data'];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Episode', 'Status', 'Progress %', 'Progress berbobot durasi %', 'Pra done/total', 'Produksi done/total', 'Pasca done/total', 'Total durasi (s)']);
            foreach ($rows as $r) {
                $ep = $r['ep'];
                fputcsv($out, [
                    $ep->name,
                    $ep->status->label(),
                    $r['persen'],
                    $r['persenDurasi'] ?? '-',
                    $r['fase'][0]['done'].'/'.$r['fase'][0]['total'],
                    $r['fase'][1]['done'].'/'.$r['fase'][1]['total'],
                    $r['fase'][2]['done'].'/'.$r['fase'][2]['total'],
                    (int) $ep->total_durasi,
                ]);
            }
            fclose($out);
        }, 'laporan-progress.csv', ['Content-Type' => 'text/csv']);
    }

    /** Tugas yang lewat deadline & belum disetujui (shot + tahap), diurutkan terlama. */
    private function overdue($epIds, $shotIds)
    {
        $hari = Carbon::today();

        $shot = TugasShot::whereIn('shot_id', $shotIds)
            ->whereNotNull('deadline')->whereDate('deadline', '<', $hari)
            ->where('status', '!=', TaskStatus::APPROVED->value)
            ->with(['shot.adegan.proyek', 'tahap', 'artists'])->get()
            ->map(fn ($t) => [
                'episode' => $t->shot?->adegan?->proyek?->name ?? '—',
                'label' => trim(($t->shot?->shot_code ?? 'Shot').' · '.($t->tahap?->name ?? '')),
                'deadline' => $t->deadline,
                'telat' => $t->deadline->diffInDays($hari),
                'siapa' => $t->artists->pluck('name')->join(', ') ?: '—',
                'status' => $t->status,
            ]);

        $tahap = TugasTahap::whereIn('project_id', $epIds)
            ->whereNotNull('deadline')->whereDate('deadline', '<', $hari)
            ->where('status', '!=', TaskStatus::APPROVED->value)
            ->with(['proyek', 'tahap', 'artis'])->get()
            ->map(fn ($t) => [
                'episode' => $t->proyek?->name ?? '—',
                'label' => $t->tahap?->name ?? 'Tahap',
                'deadline' => $t->deadline,
                'telat' => $t->deadline->diffInDays($hari),
                'siapa' => $t->artis?->name ?? '—',
                'status' => $t->status,
            ]);

        return $shot->concat($tahap)->sortByDesc('telat')->values();
    }

    /**
     * Beban kerja per artis: jumlah tugas aktif + total estimasi hari vs kapasitas.
     * Alokasi >100% = overload (diperbolehkan, hanya ditandai).
     */
    private function beban($epIds, $shotIds)
    {
        $tugas = []; // user_id => jumlah
        $hari = [];  // user_id => total estimasi_hari

        foreach (TugasShot::whereIn('shot_id', $shotIds)->where('status', '!=', TaskStatus::APPROVED->value)->with('artists:id')->get() as $t) {
            foreach ($t->artists as $a) {
                $tugas[$a->id] = ($tugas[$a->id] ?? 0) + 1;
                $hari[$a->id] = ($hari[$a->id] ?? 0) + (int) $t->estimasi_hari;
            }
        }
        foreach (TugasTahap::whereIn('project_id', $epIds)->where('status', '!=', TaskStatus::APPROVED->value)->whereNotNull('artist_id')->get() as $t) {
            $tugas[$t->artist_id] = ($tugas[$t->artist_id] ?? 0) + 1;
            $hari[$t->artist_id] = ($hari[$t->artist_id] ?? 0) + (int) $t->estimasi_hari;
        }

        $users = User::whereIn('id', array_keys($tugas))->get(['id', 'name', 'kapasitas_hari'])->keyBy('id');

        return collect($tugas)->map(function (int $n, int $uid) use ($hari, $users) {
            $u = $users[$uid] ?? null;
            $kap = max(1, (int) ($u->kapasitas_hari ?? 5));
            $h = $hari[$uid] ?? 0;

            return [
                'nama' => $u?->name ?? '—',
                'tugas' => $n,
                'hari' => $h,
                'kapasitas' => $kap,
                'persen' => (int) round($h / $kap * 100),
            ];
        })->sortByDesc('persen')->values();
    }

    /** Funnel bottleneck: jumlah shot-task aktif per tahap & statusnya. */
    private function funnel($shotIds)
    {
        return TugasShot::whereIn('shot_id', $shotIds)
            ->where('status', '!=', TaskStatus::APPROVED->value)
            ->with('tahap:id,name')->get()
            ->groupBy(fn ($t) => $t->tahap?->name ?? '—')
            ->map(fn ($g) => [
                'total' => $g->count(),
                'not_started' => $g->where('status', TaskStatus::NOT_STARTED)->count(),
                'in_progress' => $g->where('status', TaskStatus::IN_PROGRESS)->count(),
                'review' => $g->where('status', TaskStatus::REVIEW)->count(),
            ])
            ->sortByDesc('total');
    }

    /** Burndown/velocity: approval per minggu (8 minggu) + laju rata-rata + proyeksi selesai. */
    private function velocity($epIds, $shotIds): array
    {
        $shotTaskIds = TugasShot::whereIn('shot_id', $shotIds)->pluck('id');
        $tahapIds = TugasTahap::whereIn('project_id', $epIds)->pluck('id');

        $stamps = collect()
            ->concat(RevisiShot::whereIn('shot_task_id', $shotTaskIds)->where('status_to', TaskStatus::APPROVED->value)->pluck('created_at'))
            ->concat(AktivitasTahap::whereIn('tugas_tahap_id', $tahapIds)->where('kind', 'APPROVE')->pluck('created_at'))
            ->filter();

        // Ember mingguan: 8 minggu terakhir.
        $awal = Carbon::now()->startOfWeek();
        $buckets = [];
        for ($i = 7; $i >= 0; $i--) {
            $ws = $awal->copy()->subWeeks($i);
            $buckets[$ws->toDateString()] = ['label' => $ws->locale('id')->isoFormat('D MMM'), 'count' => 0];
        }
        foreach ($stamps as $ts) {
            $key = Carbon::parse($ts)->startOfWeek()->toDateString();
            if (isset($buckets[$key])) {
                $buckets[$key]['count']++;
            }
        }
        $bucketList = array_values($buckets);

        $last4 = array_slice($bucketList, -4);
        $avg = count($last4) ? array_sum(array_column($last4, 'count')) / 4 : 0;

        $sisa = TugasShot::whereIn('shot_id', $shotIds)->where('status', '!=', TaskStatus::APPROVED->value)->count()
            + TugasTahap::whereIn('project_id', $epIds)->where('status', '!=', TaskStatus::APPROVED->value)->count();

        $proyeksi = $avg > 0 && $sisa > 0
            ? Carbon::now()->addWeeks((int) ceil($sisa / $avg))->locale('id')->isoFormat('D MMM Y')
            : null;

        return ['buckets' => $bucketList, 'avg' => round($avg, 1), 'sisa' => $sisa, 'proyeksiTanggal' => $proyeksi];
    }
}
