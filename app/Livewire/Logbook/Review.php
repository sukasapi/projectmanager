<?php

namespace App\Livewire\Logbook;

use App\Actions\ReviewLogbook;
use App\Enums\StatusLogbook;
use App\Models\Logbook;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Antrean review logbook untuk mentor/supervisor. Setujui / tolak + catatan.
 * Magang tidak boleh mengakses (Gate review-logbook). Lihat UI.md §8.24.
 */
#[Layout('components.layouts.app')]
class Review extends Component
{
    /** Catatan review per entri (id => teks). */
    public array $catatan = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('review-logbook'), 403);
    }

    public function setujui(int $id): void
    {
        $this->putuskan($id, true);
    }

    public function tolak(int $id): void
    {
        $this->putuskan($id, false);
    }

    private function putuskan(int $id, bool $setujui): void
    {
        $logbook = Logbook::findOrFail($id);

        try {
            app(ReviewLogbook::class)->handle(
                auth()->user(),
                $logbook,
                $setujui,
                $this->catatan[$id] ?? null,
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        }

        unset($this->catatan[$id]);
        $this->dispatch('logbook-direview');
    }

    public function render()
    {
        $menunggu = Logbook::where('status', StatusLogbook::DIKIRIM->value)
            ->with(['pengguna', 'tugasShot.shot'])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        return view('livewire.logbook.review', [
            'menunggu' => $menunggu,
            'tz' => config('kehadiran.timezone'),
        ]);
    }
}
