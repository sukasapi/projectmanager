<?php

namespace App\Livewire\Pengaturan;

use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use App\Models\Tahap;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Kelola TEMPLATE pipeline global (project_id NULL) — Super Admin. Perubahan di sini
 * hanya berlaku untuk EPISODE BARU; episode yang sudah ada memakai snapshot-nya sendiri.
 * Lihat WORKFLOW.md / PIPELINE.md §2.
 */
#[Layout('components.layouts.app')]
class Pipeline extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $phase = '';

    public string $level = '';

    public string $name = '';

    public int $urutan = 1;

    public ?int $requiresTahapId = null;

    public bool $isActive = true;

    public string $description = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $this->phase = FaseProduksi::PRA->value;
        $this->level = LevelTahap::EPISODE->value;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $t = Tahap::global()->findOrFail($id);
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
        abort_unless(Gate::allows('manage-config'), 403);

        $validated = $this->validate([
            'phase' => ['required', Rule::in(array_column(FaseProduksi::cases(), 'value'))],
            'level' => ['required', Rule::in(array_column(LevelTahap::cases(), 'value'))],
            'name' => ['required', 'string', 'max:255'],
            'urutan' => ['required', 'integer', 'min:0', 'max:999'],
            'requiresTahapId' => ['nullable', 'integer', 'exists:kf_tahap,id'],
            'isActive' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['name' => 'nama tahap']);

        $code = Str::slug($validated['name']);

        $duplikat = Tahap::global()
            ->where('phase', $validated['phase'])
            ->where('code', $code)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->exists();

        if ($duplikat) {
            $this->addError('name', 'Tahap dengan nama serupa sudah ada pada fase ini.');

            return;
        }

        Tahap::updateOrCreate(['id' => $this->editingId, 'project_id' => null], [
            'phase' => $validated['phase'],
            'level' => $validated['level'],
            'code' => $code,
            'name' => $validated['name'],
            'urutan' => $validated['urutan'],
            'requires_tahap_id' => $validated['requiresTahapId'] ?: null,
            'is_active' => $validated['isActive'],
            'description' => $validated['description'] ?: null,
        ]);

        // Catatan: perubahan template TIDAK mengubah episode yang sudah ada
        // (mereka memakai snapshot sendiri). Hanya episode baru yang ikut.

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('tahap-tersimpan');
    }

    public function toggleAktif(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $t = Tahap::global()->findOrFail($id);
        $t->update(['is_active' => ! $t->is_active]);
    }

    public function delete(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        Tahap::global()->whereKey($id)->first()?->delete();
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

    public function render()
    {
        return view('livewire.pengaturan.pipeline', [
            'grup' => Tahap::global()->orderBy('phase')->urut()->get()->groupBy(fn ($t) => $t->phase->value),
            'fase' => FaseProduksi::cases(),
            'levels' => LevelTahap::cases(),
            'kandidatPrasyarat' => Tahap::global()->aktif()->urut()->get(),
        ]);
    }
}
