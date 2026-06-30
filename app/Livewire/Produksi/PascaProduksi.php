<?php

namespace App\Livewire\Produksi;

use App\Enums\FaseProduksi;
use Livewire\Attributes\Layout;

/**
 * Tracker Pasca-Produksi (SLRC, Editing) per episode.
 */
#[Layout('components.layouts.app')]
class PascaProduksi extends TrackerTahap
{
    protected function fase(): FaseProduksi
    {
        return FaseProduksi::PASCA;
    }

    protected function routeName(): string
    {
        return 'pasca-produksi';
    }
}
