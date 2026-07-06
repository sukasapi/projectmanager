<?php

namespace App\Livewire\Pengaturan;

use App\Enums\PeranKolomShotlist;
use App\Models\KolomShotlist;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Konfigurasi kolom Shotlist (studio-wide) — hanya Super Admin (manage-config).
 * Tambah/ubah/urutkan/nonaktifkan kolom + tandai peran (scene/shot_code/duration).
 */
#[Layout('components.layouts.app')]
class ShotlistKolom extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label = '';

    public string $tipe = 'text';

    public string $opsiText = '';

    public ?string $peran = null;

    public int $urutan = 1;

    public bool $isActive = true;

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
    }

    public function create(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $this->reset(['editingId', 'label', 'opsiText', 'peran']);
        $this->tipe = 'text';
        $this->urutan = (int) (KolomShotlist::max('urutan') + 1);
        $this->isActive = true;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $k = KolomShotlist::findOrFail($id);
        $this->editingId = $k->id;
        $this->label = $k->label;
        $this->tipe = $k->tipe;
        $this->opsiText = implode(', ', $k->opsi ?? []);
        $this->peran = $k->peran?->value;
        $this->urutan = $k->urutan;
        $this->isActive = $k->is_active;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);

        $v = $this->validate([
            'label' => ['required', 'string', 'max:100'],
            'tipe' => ['required', Rule::in(['text', 'number', 'select'])],
            'opsiText' => ['nullable', 'string', 'max:1000'],
            'peran' => ['nullable', Rule::in(array_column(PeranKolomShotlist::cases(), 'value'))],
            'urutan' => ['required', 'integer', 'min:0', 'max:999'],
            'isActive' => ['boolean'],
        ], attributes: ['label' => 'nama kolom']);

        // Satu peran hanya boleh dipakai satu kolom aktif.
        if ($v['peran']) {
            $bentrok = KolomShotlist::where('peran', $v['peran'])
                ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
                ->exists();
            if ($bentrok) {
                $this->addError('peran', 'Peran ini sudah dipakai kolom lain. Lepas dulu dari kolom tersebut.');

                return;
            }
        }

        $key = $this->editingId
            ? KolomShotlist::whereKey($this->editingId)->value('key')
            : $this->keyUnik(Str::slug($v['label'], '_'));

        $opsi = $v['tipe'] === 'select'
            ? array_values(array_filter(array_map('trim', explode(',', $v['opsiText']))))
            : null;

        KolomShotlist::updateOrCreate(['id' => $this->editingId], [
            'key' => $key,
            'label' => $v['label'],
            'tipe' => $v['tipe'],
            'opsi' => $opsi,
            'peran' => $v['peran'] ?: null,
            'urutan' => $v['urutan'],
            'is_active' => $v['isActive'],
        ]);

        $this->showForm = false;
        $this->dispatch('kolom-tersimpan');
        $this->dispatch('toast', message: 'Kolom shotlist disimpan.');
    }

    public function toggleAktif(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $k = KolomShotlist::findOrFail($id);
        $k->update(['is_active' => ! $k->is_active]);
    }

    public function delete(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        KolomShotlist::whereKey($id)->first()?->delete();
        $this->dispatch('toast', message: 'Kolom dihapus.');
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetErrorBag();
    }

    private function keyUnik(string $base): string
    {
        $key = $base ?: 'kolom';
        $i = 1;
        while (KolomShotlist::where('key', $key)->exists()) {
            $key = $base.'_'.(++$i);
        }

        return $key;
    }

    public function render()
    {
        return view('livewire.pengaturan.shotlist-kolom', [
            'kolom' => KolomShotlist::urut()->get(),
            'peranOpsi' => PeranKolomShotlist::options(),
        ]);
    }
}
