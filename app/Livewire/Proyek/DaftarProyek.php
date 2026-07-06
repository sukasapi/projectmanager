<?php

namespace App\Livewire\Proyek;

use App\Enums\ProjectStatus;
use App\Models\Klien;
use App\Models\Notifikasi;
use App\Models\Proyek;
use App\Models\Seri;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Daftar & kelola Episode. Buat/edit + tugaskan Team Lead (Supervisor), lalu Publish
 * (notifikasi ke artis) & Selesai (close). Lihat WORKFLOW.md §2.
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

    public ?int $seriId = null;

    public ?int $teamLeadId = null;

    /** Tambah klien baru langsung dari form episode. */
    public string $newClientName = '';

    public function mount(): void
    {
        $this->status = ProjectStatus::PLANNING->value;
    }

    private function pastikanBolehKelola(): void
    {
        abort_unless(Gate::allows('manage-tim'), 403);
    }

    public function create(): void
    {
        $this->pastikanBolehKelola();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->pastikanBolehKelola();
        $proyek = Proyek::findOrFail($id);

        $this->editingId = $proyek->id;
        $this->name = $proyek->name;
        $this->description = $proyek->description ?? '';
        $this->status = $proyek->status->value;
        $this->clientId = $proyek->client_id;
        $this->seriId = $proyek->series_id;
        $this->teamLeadId = $proyek->team_lead_id;
        $this->newClientName = '';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->pastikanBolehKelola();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_column(ProjectStatus::cases(), 'value'))],
            'clientId' => ['nullable', 'integer', Rule::exists('kf_klien', 'id')],
            'seriId' => ['nullable', 'integer', Rule::exists('kf_seri', 'id')],
            // Team lead episode hanya boleh dari user berperan Team Lead / supervisi (bukan artis biasa).
            'teamLeadId' => ['nullable', 'integer', Rule::exists('kf_pengguna', 'id')->whereIn('role', ['Team Lead', 'Supervisor', 'Super Admin'])],
            'newClientName' => ['nullable', 'string', 'max:255'],
        ], attributes: [
            'name' => 'nama episode',
            'teamLeadId' => 'team lead',
            'newClientName' => 'nama klien baru',
        ]);

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
                'series_id' => $validated['seriId'] ?: null,
                'team_lead_id' => $validated['teamLeadId'] ?: null,
            ]
        );

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('episode-tersimpan');
    }

    /** Publish episode: kunci fase setup & beri tahu semua artis yang ditugaskan. */
    public function publish(int $id): void
    {
        $proyek = Proyek::findOrFail($id);
        abort_unless($proyek->dapatDikelola(auth()->user()), 403);

        if ($proyek->isPublished() || $proyek->isClosed()) {
            return;
        }

        $proyek->update([
            'published_at' => now(),
            'status' => ProjectStatus::IN_PROGRESS->value,
        ]);

        Notifikasi::kirimBanyak(
            $proyek->assignedUserIds(),
            "Episode dipublish: {$proyek->name}",
            'Anda ditugaskan pada episode ini. Silakan mulai mengerjakan tracker.',
            route('shot-matrix'),
            'publish',
        );

        $this->dispatch('episode-tersimpan');
        $this->dispatch('toast', message: "Episode \"{$proyek->name}\" dipublish — notifikasi terkirim.");
    }

    /** Selesai/close episode. */
    public function tutup(int $id): void
    {
        $proyek = Proyek::findOrFail($id);
        abort_unless($proyek->dapatDikelola(auth()->user()), 403);

        if (! $proyek->isPublished()) {
            return;
        }

        $proyek->update([
            'closed_at' => now(),
            'status' => ProjectStatus::COMPLETED->value,
        ]);

        $this->dispatch('episode-tersimpan');
        $this->dispatch('toast', message: "Episode \"{$proyek->name}\" ditandai selesai.");
    }

    /** Buka kembali episode yang sudah ditutup — hanya Super Admin / Supervisor. */
    public function bukaKembali(int $id): void
    {
        $proyek = Proyek::findOrFail($id);
        abort_unless($proyek->dapatDibukaKembali(auth()->user()), 403);

        if (! $proyek->isClosed()) {
            return;
        }

        $proyek->update([
            'closed_at' => null,
            'status' => ProjectStatus::IN_PROGRESS->value,
        ]);

        $this->dispatch('episode-tersimpan');
        $this->dispatch('toast', message: "Episode \"{$proyek->name}\" dibuka kembali.");
    }

    public function delete(int $id): void
    {
        $this->pastikanBolehKelola();
        Proyek::whereKey($id)->first()?->delete();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'clientId', 'seriId', 'teamLeadId', 'newClientName']);
        $this->status = ProjectStatus::PLANNING->value;
        $this->resetErrorBag();
    }

    public function render()
    {
        $episodes = Proyek::query()
            ->with(['klien', 'teamLead'])
            ->withCount('adegan')
            ->withSum('adegan as total_durasi', 'total_duration')
            ->orderByDesc('id')
            ->get();

        return view('livewire.proyek.daftar-proyek', [
            'episodes' => $episodes,
            'daftarKlien' => Klien::orderBy('name')->get(['id', 'name']),
            'daftarSeri' => Seri::orderBy('name')->get(['id', 'name']),
            'daftarStatus' => ProjectStatus::cases(),
            // Kandidat team lead: hanya peran Team Lead / supervisi.
            'daftarArtis' => User::where('is_active', true)
                ->whereIn('role', ['Team Lead', 'Supervisor', 'Super Admin'])
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
            'bisaKelola' => Gate::allows('manage-tim'),
        ]);
    }
}
