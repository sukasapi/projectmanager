<?php

namespace App\Livewire\Notifikasi;

use App\Models\Notifikasi;
use Livewire\Component;

/**
 * Lonceng notifikasi di topbar. Polling ringan untuk jumlah belum dibaca.
 * Lihat WORKFLOW.md §3.
 */
class Lonceng extends Component
{
    public function buka(int $id)
    {
        $n = Notifikasi::where('user_id', auth()->id())->find($id);
        if (! $n) {
            return null;
        }

        $n->update(['read_at' => now()]);

        if ($n->url) {
            return $this->redirect($n->url, navigate: true);
        }

        return null;
    }

    public function tandaiSemua(): void
    {
        Notifikasi::where('user_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('livewire.notifikasi.lonceng', [
            'items' => Notifikasi::where('user_id', auth()->id())->latest()->limit(12)->get(),
            'belum' => Notifikasi::where('user_id', auth()->id())->whereNull('read_at')->count(),
        ]);
    }
}
