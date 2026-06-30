<?php

namespace App\Livewire;

use App\Enums\FaseProduksi;
use App\Enums\TaskStatus;
use App\Models\TugasTahap;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * "Tugas Saya" — antrean kerja pribadi: shot-task (Produksi) + tahap (Pra/Pasca)
 * yang ditugaskan ke user & belum APPROVED, di episode yang sudah dipublish.
 */
#[Layout('components.layouts.app')]
class TugasSaya extends Component
{
    public function render()
    {
        $uid = auth()->id();

        // Shot-task milik saya (pivot penugasan) yang belum selesai.
        $shot = auth()->user()->tugasShot()
            ->where('kf_tugas_shot.status', '!=', TaskStatus::APPROVED->value)
            ->with(['shot.adegan.proyek', 'tahap'])
            ->get()
            ->filter(fn ($t) => $t->shot?->adegan?->proyek?->isPublished())
            ->map(fn ($t) => [
                'episode' => $t->shot?->adegan?->proyek?->name ?? '—',
                'kind' => 'Produksi',
                'label' => trim(($t->shot?->shot_code ?? 'Shot').' · '.($t->tahap?->name ?? '')),
                'status' => $t->status,
                'deadline' => $t->deadline,
                'url' => route('shot-matrix', ['episode' => $t->shot?->adegan?->project_id]),
            ]);

        // Tahap (Pra/Pasca) milik saya yang belum selesai.
        $tahap = TugasTahap::where('artist_id', $uid)
            ->where('status', '!=', TaskStatus::APPROVED->value)
            ->with(['proyek', 'tahap'])
            ->get()
            ->filter(fn ($t) => $t->proyek?->isPublished())
            ->map(fn ($t) => [
                'episode' => $t->proyek?->name ?? '—',
                'kind' => $t->tahap?->phase?->label() ?? 'Tahap',
                'label' => $t->tahap?->name ?? 'Tahap',
                'status' => $t->status,
                'deadline' => $t->deadline,
                'url' => ($t->tahap?->phase === FaseProduksi::PASCA ? route('pasca-produksi') : route('pra-produksi')).'?episode='.$t->project_id,
            ]);

        $grup = $shot->concat($tahap)
            ->sortBy(fn ($i) => $i['deadline']?->timestamp ?? PHP_INT_MAX)
            ->groupBy('episode');

        return view('livewire.tugas-saya', [
            'grup' => $grup,
            'total' => $shot->count() + $tahap->count(),
        ]);
    }
}
