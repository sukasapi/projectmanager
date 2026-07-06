<?php

namespace App\Livewire\Produksi;

use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use App\Enums\RevisionStatus;
use App\Enums\TaskStatus;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\Tahap;
use App\Models\TugasShot;
use App\Models\TugasTahap;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Editor pipeline (susunan TAHAP) khusus SATU episode — menyunting snapshot
 * kf_tahap milik episode (project_id = {episode}). Akses: Proyek::dapatDikelola
 * (Supervisor/Super Admin atau Team Lead episode; CLOSED read-only). Template
 * GLOBAL tetap milik Super Admin (Pengaturan\Pipeline). Menambah/mengaktifkan
 * tahap Produksi level-SHOT membuat sub-task untuk shot yang sudah ada (backfill).
 * Lihat 2026-07-01_peran-team-lead-hak-akses.md & PIPELINE.md.
 */
#[Layout('components.layouts.app')]
class PipelineEpisode extends Component
{
    public int $proyekId;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $phase = '';

    public string $level = '';

    public string $name = '';

    public int $urutan = 1;

    public ?int $requiresTahapId = null;

    public bool $isActive = true;

    public string $description = '';

    public function mount(Proyek $proyek): void
    {
        abort_unless($proyek->dapatDikelola(auth()->user()), 403);
        $proyek->pastikanPipeline(); // self-heal bila snapshot hilang
        $this->proyekId = $proyek->id;
        $this->phase = FaseProduksi::PRA->value;
        $this->level = LevelTahap::EPISODE->value;
    }

    private function proyek(): Proyek
    {
        return Proyek::findOrFail($this->proyekId);
    }

    private function pastikanBoleh(): void
    {
        abort_unless($this->proyek()->dapatDikelola(auth()->user()), 403);
    }

    public function create(): void
    {
        $this->pastikanBoleh();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->pastikanBoleh();
        $t = Tahap::milikEpisode($this->proyekId)->findOrFail($id);

        $this->editingId = $t->id;
        $this->phase = $t->phase->value;
        $this->level = $t->level->value;
        $this->name = $t->name;
        $this->urutan = $t->urutan;
        $this->requiresTahapId = $t->requires_tahap_id;
        $this->isActive = $t->is_active;
        $this->description = $t->description ?? '';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->pastikanBoleh();

        $validated = $this->validate([
            'phase' => ['required', Rule::in(array_column(FaseProduksi::cases(), 'value'))],
            'level' => ['required', Rule::in(array_column(LevelTahap::cases(), 'value'))],
            'name' => ['required', 'string', 'max:255'],
            'urutan' => ['required', 'integer', 'min:0', 'max:999'],
            'requiresTahapId' => ['nullable', 'integer', Rule::exists('kf_tahap', 'id')->where('project_id', $this->proyekId)],
            'isActive' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['name' => 'nama tahap']);

        $code = Str::slug($validated['name']);

        $duplikat = Tahap::milikEpisode($this->proyekId)
            ->where('phase', $validated['phase'])
            ->where('code', $code)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->exists();

        if ($duplikat) {
            $this->addError('name', 'Tahap dengan nama serupa sudah ada pada fase ini.');

            return;
        }

        if ($this->editingId) {
            $lama = Tahap::milikEpisode($this->proyekId)->find($this->editingId);

            // Guardrail: tahap yang sudah dipakai tugas tak boleh berpindah fase/level (menghindari orphan).
            if ($lama && $this->tahapTerpakai($lama)
                && ($lama->phase->value !== $validated['phase'] || $lama->level->value !== $validated['level'])) {
                $this->addError('name', 'Fase/level tak bisa diubah karena tahap sudah dipakai tugas. Buat tahap baru bila perlu.');

                return;
            }

            // Guardrail: cegah siklus dependensi (A butuh B butuh A).
            if ($validated['requiresTahapId'] && $this->membentukSiklus($this->editingId, (int) $validated['requiresTahapId'])) {
                $this->addError('requiresTahapId', 'Prasyarat ini membentuk siklus dependensi.');

                return;
            }
        }

        $tahap = Tahap::updateOrCreate(['id' => $this->editingId, 'project_id' => $this->proyekId], [
            'phase' => $validated['phase'],
            'level' => $validated['level'],
            'code' => $code,
            'name' => $validated['name'],
            'urutan' => $validated['urutan'],
            'requires_tahap_id' => $validated['requiresTahapId'] ?: null,
            'is_active' => $validated['isActive'],
            'description' => $validated['description'] ?: null,
        ]);

        $this->backfillJikaShot($tahap);

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('tahap-tersimpan');
        $this->dispatch('toast', message: 'Tahap tersimpan.', icon: 'success');
    }

    public function toggleAktif(int $id): void
    {
        $this->pastikanBoleh();
        $t = Tahap::milikEpisode($this->proyekId)->findOrFail($id);
        $t->update(['is_active' => ! $t->is_active]);
        $this->backfillJikaShot($t->refresh());
    }

    public function delete(int $id): void
    {
        $this->pastikanBoleh();
        $t = Tahap::milikEpisode($this->proyekId)->findOrFail($id);

        if ($this->tahapTerpakai($t)) {
            $this->dispatch('toast', message: 'Tahap sudah dipakai tugas — nonaktifkan saja, tidak dapat dihapus.', icon: 'error');

            return;
        }

        $t->delete();
        $this->dispatch('toast', message: 'Tahap dihapus.', icon: 'success');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'requiresTahapId', 'description']);
        $this->phase = FaseProduksi::PRA->value;
        $this->level = LevelTahap::EPISODE->value;
        $this->urutan = 1;
        $this->isActive = true;
        $this->resetErrorBag();
    }

    /** ID seluruh shot pada episode ini. */
    private function shotIdsEpisode(): Collection
    {
        return Shot::whereIn('scene_id', $this->proyek()->adegan()->pluck('id'))->pluck('id');
    }

    /** Tahap dipakai oleh tugas (shot atau tahap-episode) sehingga tak boleh dihapus keras. */
    private function tahapTerpakai(Tahap $tahap): bool
    {
        return TugasShot::where('tahap_id', $tahap->id)->whereIn('shot_id', $this->shotIdsEpisode())->exists()
            || TugasTahap::where('project_id', $this->proyekId)->where('tahap_id', $tahap->id)->exists();
    }

    /** Apakah menetapkan prasyarat $requiresId pada $tahapId membentuk siklus dependensi? */
    private function membentukSiklus(int $tahapId, int $requiresId): bool
    {
        $current = $requiresId;
        $langkah = 0;

        while ($current && $langkah++ < 100) {
            if ($current === $tahapId) {
                return true;
            }
            $current = (int) Tahap::whereKey($current)->value('requires_tahap_id');
        }

        return false;
    }

    /** Backfill sub-task untuk tahap Produksi level-SHOT yang aktif ke shot yang sudah ada. */
    private function backfillJikaShot(Tahap $tahap): void
    {
        if (! $tahap->is_active || $tahap->phase !== FaseProduksi::PRODUKSI || $tahap->level !== LevelTahap::SHOT) {
            return;
        }

        $shotIds = $this->shotIdsEpisode();

        // Pulihkan baris yang ter-soft-delete (hindari bentrok unique shot_id+tahap_id).
        $trashed = TugasShot::onlyTrashed()->where('tahap_id', $tahap->id)->whereIn('shot_id', $shotIds)->get();
        $trashed->each->restore();

        $sudahAda = TugasShot::where('tahap_id', $tahap->id)->whereIn('shot_id', $shotIds)->pluck('shot_id');

        foreach ($shotIds->diff($sudahAda) as $shotId) {
            TugasShot::create([
                'shot_id' => $shotId,
                'tahap_id' => $tahap->id,
                'status' => TaskStatus::NOT_STARTED->value,
                'revision_status' => RevisionStatus::NONE->value,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.produksi.pipeline-episode', [
            'proyek' => $this->proyek(),
            'grup' => Tahap::milikEpisode($this->proyekId)->orderBy('phase')->urut()->get()->groupBy(fn ($t) => $t->phase->value),
            'fase' => FaseProduksi::cases(),
            'levels' => LevelTahap::cases(),
            'kandidatPrasyarat' => Tahap::milikEpisode($this->proyekId)->aktif()->urut()->get(),
        ]);
    }
}
