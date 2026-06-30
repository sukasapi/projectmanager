<?php

namespace App\Livewire\Produksi;

use App\Enums\FaseProduksi;
use Livewire\Attributes\Layout;

/**
 * Tracker Pra-Produksi (Script, Shotlist, STB, Animatic, …) per episode.
 */
#[Layout('components.layouts.app')]
class PraProduksi extends TrackerTahap
{
    protected function fase(): FaseProduksi
    {
        return FaseProduksi::PRA;
    }

    protected function routeName(): string
    {
        return 'pra-produksi';
    }
}
