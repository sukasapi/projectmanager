<?php

namespace App\Livewire\Aset;

use App\Enums\AssetTask;
use App\Enums\AssetType;
use App\Enums\EmploymentType;
use App\Enums\TaskStatus;
use App\Models\Aset;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Manajemen Aset per episode (Tier C1): CRUD aset (karakter/env/prop) + penugasan +
 * status per tahap aset (Modeling/Texturing/Rigging) + breakdown ke shot yang memakainya.
 * Pola: pilih episode (kartu) → kelola aset. Akses setup = Proyek::dapatDikelola.
 * Lihat 2026-07-01_roadmap-produksi-tier-c.md §C1.
 */
#[Layout('components.layouts.app')]
class Manager extends Component
{
    public ?int $proyekId = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $type = 'CHARACTER';

    public string $name = '';

    public string $task = 'MODELING';

    public ?int $artistId = null;

    public string $status = 'NOT_STARTED';

    public string $fileUrl = '';

    public string $notes = '';

    /** Shot yang memakai aset ini (breakdown). */
    public array $shotIds = [];

    /** Aset bersama seri (dapat dipakai lintas episode dalam seri yang sama). */
    public bool $bersama = false;

    public function mount(): void
    {
        $episode = (int) request()->integer('episode');
        if ($episode && $this->bolehAkses($episode)) {
            $this->proyekId = $episode;
        }
    }

    private function bolehAkses(int $proyekId): bool
    {
        return Gate::allows('manage-tim')
            || Proyek::whereKey($proyekId)->untukUser(auth()->id() ?? 0)->exists();
    }

    private function dapatKelola(): bool
    {
        $proyek = $this->proyekId ? Proyek::find($this->proyekId) : null;

        return (bool) ($proyek && $proyek->dapatDikelola(auth()->user()));
    }

    public function pilihEpisode(int $id): void
    {
        if ($this->bolehAkses($id)) {
            $this->proyekId = $id;
        }
    }

    public function gantiEpisode(): void
    {
        $this->proyekId = null;
        $this->showForm = false;
    }

    public function create(): void
    {
        abort_unless($this->dapatKelola(), 403);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->dapatKelola(), 403);
        $proyek = Proyek::findOrFail($this->proyekId);
        $aset = Aset::with('shots:id')->findOrFail($id);
        // Boleh mengedit aset milik episode ini ATAU aset bersama dari seri yang sama.
        abort_unless($aset->project_id === $this->proyekId || ($aset->series_id && $aset->series_id === $proyek->series_id), 403);

        $this->editingId = $aset->id;
        $this->type = $aset->type->value;
        $this->name = $aset->name;
        $this->task = $aset->task->value;
        $this->artistId = $aset->artist_id;
        $this->status = $aset->status->value;
        $this->fileUrl = $aset->file_url ?? '';
        $this->notes = $aset->notes ?? '';
        $this->bersama = $aset->series_id !== null;
        // Breakdown yang ditampilkan hanya shot milik episode ini (aset bersama tetap simpan tautan episode lain).
        $shotIniSemua = Shot::whereIn('scene_id', $proyek->adegan()->pluck('id'))->pluck('id');
        $this->shotIds = $aset->shots->pluck('id')->intersect($shotIniSemua)->values()->all();
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->dapatKelola(), 403);

        $v = $this->validate([
            'type' => ['required', Rule::in(array_column(AssetType::cases(), 'value'))],
            'name' => ['required', 'string', 'max:255'],
            'task' => ['required', Rule::in(array_column(AssetTask::cases(), 'value'))],
            'artistId' => ['nullable', 'integer', 'exists:kf_pengguna,id'],
            'status' => ['required', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'fileUrl' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shotIds' => ['array'],
            'shotIds.*' => ['integer', 'exists:kf_shot,id'],
        ], attributes: ['name' => 'nama aset']);

        // Magang tidak boleh menyetujui (selaras guard shot-task).
        if ($v['status'] === TaskStatus::APPROVED->value && auth()->user()?->employment_type === EmploymentType::INTERN) {
            $this->addError('status', 'Pengguna magang tidak diizinkan menyetujui aset.');

            return;
        }

        $proyek = Proyek::findOrFail($this->proyekId);
        $seriId = ($this->bersama && $proyek->series_id) ? $proyek->series_id : null;

        $atribut = [
            'series_id' => $seriId,
            'type' => $v['type'],
            'name' => $v['name'],
            'task' => $v['task'],
            'artist_id' => $v['artistId'] ?: null,
            'status' => $v['status'],
            'file_url' => $v['fileUrl'] ?: null,
            'notes' => $v['notes'] ?: null,
        ];

        if ($this->editingId) {
            $aset = Aset::findOrFail($this->editingId);
            abort_unless($aset->project_id === $this->proyekId || ($aset->series_id && $aset->series_id === $proyek->series_id), 403);
            $aset->update($atribut);
        } else {
            $aset = Aset::create($atribut + ['project_id' => $this->proyekId]);
        }

        // Breakdown: hanya shot milik episode ini. Aset bersama TIDAK melepas tautan shot episode lain.
        $shotIniSemua = Shot::whereIn('scene_id', $proyek->adegan()->pluck('id'))->pluck('id');
        $shotSah = $shotIniSemua->intersect($v['shotIds']);
        if ($seriId) {
            $aset->shots()->detach($shotIniSemua->diff($shotSah));
            $aset->shots()->syncWithoutDetaching($shotSah);
        } else {
            $aset->shots()->sync($shotSah);
        }

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('aset-tersimpan');
        $this->dispatch('toast', message: 'Aset disimpan.');
    }

    public function delete(int $id): void
    {
        abort_unless($this->dapatKelola(), 403);
        Aset::where('project_id', $this->proyekId)->whereKey($id)->first()?->delete();
        $this->dispatch('toast', message: 'Aset dihapus.');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'artistId', 'fileUrl', 'notes', 'shotIds', 'bersama']);
        $this->type = 'CHARACTER';
        $this->task = 'MODELING';
        $this->status = 'NOT_STARTED';
        $this->resetErrorBag();
    }

    public function render()
    {
        if (! $this->proyekId) {
            $supervisor = Gate::allows('manage-tim');
            $episodes = Proyek::query()
                ->when(! $supervisor, fn ($q) => $q->untukUser(auth()->id() ?? 0))
                ->withCount('aset')->orderByDesc('id')->get();

            return view('livewire.aset.manager', ['episodes' => $episodes]);
        }

        $proyek = Proyek::findOrFail($this->proyekId);

        // Aset episode ini + aset bersama dari seri yang sama (reuse lintas-episode).
        $aset = Aset::query()
            ->where(function ($q) use ($proyek) {
                $q->where('project_id', $proyek->id);
                if ($proyek->series_id) {
                    $q->orWhere('series_id', $proyek->series_id);
                }
            })
            ->with(['artist', 'shots:id,shot_code'])->orderBy('name')->get();

        return view('livewire.aset.manager', [
            'proyek' => $proyek,
            'bisaKelola' => $this->dapatKelola(),
            'punyaSeri' => $proyek->series_id !== null,
            'asetPerTipe' => $aset->groupBy(fn ($a) => $a->type->value),
            'tipeOpsi' => AssetType::cases(),
            'taskOpsi' => AssetTask::cases(),
            'statusOpsi' => TaskStatus::cases(),
            'daftarArtis' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'daftarShot' => Shot::whereIn('scene_id', $proyek->adegan()->pluck('id'))->orderByRaw('LENGTH(shot_code), shot_code')->get(['id', 'shot_code']),
        ]);
    }
}
