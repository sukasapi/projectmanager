<?php

namespace App\Livewire\Notifikasi;

use App\Models\Notifikasi;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman daftar notifikasi pengguna. Klik item = tandai dibaca + buka tautan terkait.
 */
#[Layout('components.layouts.app')]
class Daftar extends Component
{
    use WithPagination;

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
        return view('livewire.notifikasi.daftar', [
            'items' => Notifikasi::where('user_id', auth()->id())->latest()->paginate(20),
            'belum' => Notifikasi::where('user_id', auth()->id())->whereNull('read_at')->count(),
        ]);
    }
}
