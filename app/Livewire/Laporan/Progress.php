<?php

namespace App\Livewire\Laporan;

use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use App\Enums\TaskStatus;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\Tahap;
use App\Models\TugasShot;
use App\Models\TugasTahap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Laporan progress per episode: persentase penyelesaian per fase (Pra/Produksi/Pasca).
 * Supervisor melihat semua episode; lainnya hanya yang ditugaskan padanya.
 */
#[Layout('components.layouts.app')]
class Progress extends Component
{
    public function render()
    {
        $supervisor = Gate::allows('manage-tim');

        $episodes = Proyek::query()
            ->when(! $supervisor, fn ($q) => $q->untukUser(auth()->id() ?? 0))
            ->with('klien')
            ->withSum('adegan as total_durasi', 'total_duration')
            ->orderByDesc('id')
            ->get();

        $data = $episodes->map(function (Proyek $ep) {
            $praTotal = $this->tahapTotal($ep->id, FaseProduksi::PRA);
            $pascaTotal = $this->tahapTotal($ep->id, FaseProduksi::PASCA);
            $praDone = $this->tahapDone($ep->id, FaseProduksi::PRA);
            $pascaDone = $this->tahapDone($ep->id, FaseProduksi::PASCA);

            $shotIds = Shot::whereIn('scene_id', $ep->adegan()->pluck('id'))->pluck('id');
            $prodTotal = TugasShot::whereIn('shot_id', $shotIds)->count();
            $prodDone = TugasShot::whereIn('shot_id', $shotIds)->where('status', TaskStatus::APPROVED->value)->count();

            $fase = [
                ['label' => 'Pra-Produksi', 'done' => $praDone, 'total' => $praTotal],
                ['label' => 'Produksi', 'done' => $prodDone, 'total' => $prodTotal],
                ['label' => 'Pasca-Produksi', 'done' => $pascaDone, 'total' => $pascaTotal],
            ];

            $totDone = $praDone + $prodDone + $pascaDone;
            $totAll = $praTotal + $prodTotal + $pascaTotal;

            return [
                'ep' => $ep,
                'fase' => $fase,
                'persen' => $totAll > 0 ? (int) round($totDone / $totAll * 100) : 0,
            ];
        });

        $epIds = $episodes->pluck('id');
        $shotIds = Shot::whereIn('scene_id', Adegan::whereIn('project_id', $epIds)->pluck('id'))->pluck('id');

        return view('livewire.laporan.progress', [
            'data' => $data,
            'overdue' => $this->overdue($epIds, $shotIds),
            'beban' => $this->beban($epIds, $shotIds),
        ]);
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

    /** Beban kerja per artis: jumlah tugas aktif (belum disetujui) yang ditugaskan. */
    private function beban($epIds, $shotIds)
    {
        $hitung = [];

        foreach (TugasShot::whereIn('shot_id', $shotIds)->where('status', '!=', TaskStatus::APPROVED->value)->with('artists')->get() as $t) {
            foreach ($t->artists as $a) {
                $hitung[$a->name] = ($hitung[$a->name] ?? 0) + 1;
            }
        }

        foreach (TugasTahap::whereIn('project_id', $epIds)->where('status', '!=', TaskStatus::APPROVED->value)->whereNotNull('artist_id')->with('artis')->get() as $t) {
            $n = $t->artis?->name;
            if ($n) {
                $hitung[$n] = ($hitung[$n] ?? 0) + 1;
            }
        }

        arsort($hitung);

        return collect($hitung);
    }

    private function tahapTotal(int $epId, FaseProduksi $fase): int
    {
        return Tahap::milikEpisode($epId)->aktif()->fase($fase)
            ->where('level', LevelTahap::EPISODE->value)->count();
    }

    private function tahapDone(int $epId, FaseProduksi $fase): int
    {
        return TugasTahap::where('project_id', $epId)
            ->where('status', TaskStatus::APPROVED->value)
            ->whereHas('tahap', fn ($q) => $q->where('phase', $fase->value))
            ->count();
    }
}
