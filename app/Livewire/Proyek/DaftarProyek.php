<?php

namespace App\Livewire\Proyek;

use App\Enums\ProjectStatus;
use App\Models\Klien;
use App\Models\Proyek;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Daftar & kelola Episode (Project/Episode). Form buat/edit tergabung sebagai modal,
 * termasuk pengaitan ke Klien. Lihat UI.md §8.5–8.6.
 */
#[Layout('components.layouts.app')]
class DaftarProyek extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $status = '';

    public ?int $clientId = null;

    /** Tambah klien baru langsung dari form episode. */
    public string $newClientName = '';

    public function mount(): void
    {
        $this->status = ProjectStatus::PLANNING->value;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $proyek = Proyek::findOrFail($id);

        $this->editingId = $proyek->id;
        $this->name = $proyek->name;
        $this->description = $proyek->description ?? '';
        $this->status = $proyek->status->value;
        $this->clientId = $proyek->client_id;
        $this->newClientName = '';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_column(ProjectStatus::cases(), 'value'))],
            'clientId' => ['nullable', 'integer', Rule::exists('kf_klien', 'id')],
            'newClientName' => ['nullable', 'string', 'max:255'],
        ], attributes: [
            'name' => 'nama episode',
            'newClientName' => 'nama klien baru',
        ]);

        // Bila mengisi klien baru, buat dulu lalu pakai sebagai client_id.
        $clientId = $this->clientId;
        if (trim($this->newClientName) !== '') {
            $clientId = Klien::create(['name' => trim($this->newClientName)])->id;
        }

        Proyek::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $validated['name'],
                'description' => $validated['description'] ?: null,
                'status' => $validated['status'],
                'client_id' => $clientId,
            ]
        );

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('episode-tersimpan');
    }

    public function delete(int $id): void
    {
        Proyek::whereKey($id)->first()?->delete();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'clientId', 'newClientName']);
        $this->status = ProjectStatus::PLANNING->value;
        $this->resetErrorBag();
    }

    public function render()
    {
        $episodes = Proyek::query()
            ->with('klien')
            ->withCount('adegan')
            ->withSum('adegan as total_durasi', 'total_duration')
            ->orderByDesc('id')
            ->get();

        return view('livewire.proyek.daftar-proyek', [
            'episodes' => $episodes,
            'daftarKlien' => Klien::orderBy('name')->get(['id', 'name']),
            'daftarStatus' => ProjectStatus::cases(),
        ]);
    }
}
