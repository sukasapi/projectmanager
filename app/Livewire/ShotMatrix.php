<?php

namespace App\Livewire;

use App\Actions\CreateShot;
use App\Enums\ShotTaskType;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\Shot;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ShotMatrix extends Component
{
    public ?int $proyekId = null;

    /** Form tambah shot. */
    #[Validate('required|integer')]
    public ?int $newSceneId = null;

    #[Validate('required|string|max:255')]
    public string $newShotCode = '';

    #[Validate('required|integer|min:0|max:86400')]
    public int $newDurationSeconds = 0;

    public function mount(): void
    {
        $this->proyekId ??= Proyek::query()->orderBy('id')->value('id');
    }

    /**
     * Tambah shot baru. total_duration scene otomatis diperbarui ShotObserver.
     */
    public function addShot(CreateShot $createShot): void
    {
        $this->validate([
            'newSceneId' => ['required', 'integer', Rule::exists('kf_adegan', 'id')],
            'newShotCode' => [
                'required', 'string', 'max:255',
                Rule::unique('kf_shot', 'shot_code')
                    ->where(fn ($q) => $q->where('scene_id', $this->newSceneId)),
            ],
            'newDurationSeconds' => ['required', 'integer', 'min:0', 'max:86400'],
        ], attributes: [
            'newSceneId' => 'scene',
            'newShotCode' => 'kode shot',
            'newDurationSeconds' => 'durasi',
        ]);

        $createShot->handle([
            'scene_id' => $this->newSceneId,
            'shot_code' => $this->newShotCode,
            'duration_seconds' => $this->newDurationSeconds,
        ]);

        $this->reset('newShotCode', 'newDurationSeconds');
        $this->dispatch('shot-tersimpan');
    }

    /** Menyegar matriks setelah aksi di panel review. */
    #[On('shot-tersimpan')]
    public function refreshMatrix(): void
    {
        // Tubuh kosong: cukup memicu render ulang komponen.
    }

    public function deleteShot(int $shotId): void
    {
        Shot::whereKey($shotId)->first()?->delete();
        $this->dispatch('shot-tersimpan');
    }

    /**
     * Buka panel review untuk satu shot-task (ditangani komponen ReviewPanel — Langkah 4).
     */
    public function review(int $tugasShotId): void
    {
        $this->dispatch('buka-review', tugasShotId: $tugasShotId);
    }

    public function render()
    {
        $taskTypes = ShotTaskType::cases();

        $proyek = $this->proyekId
            ? Proyek::with([
                'adegan' => fn ($q) => $q->orderBy('scene_name'),
                'adegan.shot' => fn ($q) => $q->orderBy('shot_code'),
                'adegan.shot.tugasShot.artists',
            ])->find($this->proyekId)
            : null;

        return view('livewire.shot-matrix', [
            'proyek' => $proyek,
            'taskTypes' => $taskTypes,
            'daftarProyek' => Proyek::orderBy('name')->get(['id', 'name']),
            'daftarAdegan' => $this->proyekId
                ? Adegan::where('project_id', $this->proyekId)->orderBy('scene_name')->get(['id', 'scene_name'])
                : collect(),
        ]);
    }
}
