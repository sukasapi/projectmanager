<?php

namespace App\Livewire;

use App\Enums\FaseProduksi;
use App\Models\Proyek;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Asset Library — daftar seluruh link file yang disertakan di semua proses
 * (Pra, Produksi, Pasca, Aset), dikelompokkan per episode dan disusun sebagai pohon:
 * Episode → Fase/Pipeline → (Scene → Shot) → Task → link file.
 * Supervisor melihat semua episode; lainnya hanya yang ditugaskan padanya.
 */
#[Layout('components.layouts.app')]
class Pustaka extends Component
{
    public string $cari = '';

    public function render()
    {
        $supervisor = Gate::allows('manage-tim');

        $episodes = Proyek::query()
            ->when(! $supervisor, fn ($q) => $q->untukUser(auth()->id() ?? 0))
            ->with([
                'klien',
                'tugasTahap' => fn ($q) => $q->whereNotNull('file_url')->with(['tahap', 'artis']),
                'adegan' => fn ($q) => $q->orderByRaw('LENGTH(scene_name), scene_name'),
                'adegan.shot' => fn ($q) => $q->orderByRaw('LENGTH(shot_code), shot_code'),
                'adegan.shot.tugasShot' => fn ($q) => $q->whereNotNull('preview_url')->with(['tahap', 'artists']),
                'aset' => fn ($q) => $q->whereNotNull('file_url')->with('artist'),
            ])
            ->orderByDesc('id')
            ->get();

        $pohon = $episodes
            ->map(fn ($ep) => $this->bangunEpisode($ep))
            ->filter(fn ($node) => ! empty($node['children']))
            ->values()
            ->all();

        return view('livewire.pustaka', ['pohon' => $pohon]);
    }

    /** Bangun satu node episode beserta cabang fase. */
    private function bangunEpisode(Proyek $ep): array
    {
        $children = [];

        if ($pra = $this->itemFase($ep, FaseProduksi::PRA)) {
            $children[] = ['label' => 'Pra-Produksi', 'badge' => count($pra).' file', 'children' => $pra];
        }

        $produksi = $this->cabangProduksi($ep);
        if (! empty($produksi['children'])) {
            $children[] = $produksi;
        }

        if ($pasca = $this->itemFase($ep, FaseProduksi::PASCA)) {
            $children[] = ['label' => 'Pasca-Produksi', 'badge' => count($pasca).' file', 'children' => $pasca];
        }

        $aset = $ep->aset->map(fn ($a) => [
            'label' => $a->name,
            'badge' => $a->type?->label(),
            'url' => $a->file_url,
            'meta' => trim(($a->artist?->name ?? 'Tanpa artis').' · '.($a->status?->label() ?? '')),
            'children' => [],
        ])->all();
        if ($aset) {
            $children[] = ['label' => 'Aset', 'badge' => count($aset).' file', 'children' => $aset];
        }

        return ['label' => $ep->name, 'badge' => $ep->klien?->name, 'children' => $children];
    }

    /** Leaf node untuk tahap level-episode (Pra/Pasca) yang punya file_url. */
    private function itemFase(Proyek $ep, FaseProduksi $fase): array
    {
        return $ep->tugasTahap
            ->filter(fn ($t) => $t->tahap?->phase === $fase)
            ->sortBy(fn ($t) => $t->tahap?->urutan ?? 0)
            ->map(fn ($t) => [
                'label' => $t->tahap?->name ?? 'Tahap',
                'url' => $t->file_url,
                'meta' => trim(($t->artis?->name ?? 'Tanpa artis').' · '.($t->status?->label() ?? '')),
                'children' => [],
            ])->values()->all();
    }

    /** Cabang Produksi: Scene → Shot → Task(shot) yang punya preview_url. */
    private function cabangProduksi(Proyek $ep): array
    {
        $scenes = [];

        foreach ($ep->adegan as $adegan) {
            $shots = [];
            foreach ($adegan->shot as $shot) {
                $tasks = $shot->tugasShot->map(fn ($t) => [
                    'label' => $t->tahap?->name ?? 'Task',
                    'url' => $t->preview_url,
                    'meta' => trim($t->artists->pluck('name')->join(', ').' · '.($t->status?->label() ?? '')),
                    'children' => [],
                ])->values()->all();

                if ($tasks) {
                    $shots[] = ['label' => $shot->shot_code, 'badge' => count($tasks).' file', 'children' => $tasks];
                }
            }

            if ($shots) {
                $scenes[] = ['label' => $adegan->scene_name, 'children' => $shots];
            }
        }

        return ['label' => 'Produksi', 'children' => $scenes];
    }
}
