<?php

namespace App\Livewire\Pengaturan;

use App\Enums\PeranKolomShotlist;
use App\Models\GayaShotlist;
use App\Models\KolomShotlist;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Konfigurasi Style Shotlist — hanya Super Admin (manage-config).
 * Kelola beberapa gaya (style); tiap gaya punya susunan kolom sendiri.
 * Seri memilih gaya di halaman Seri; berlaku untuk seluruh episodenya.
 * Lihat docs/2026-07-13_shotlist-style.md.
 */
#[Layout('components.layouts.app')]
class ShotlistKolom extends Component
{
    /** Gaya yang sedang dibuka (kolomnya ditampilkan/dikelola). */
    public ?int $styleId = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label = '';

    public string $tipe = 'text';

    public string $opsiText = '';

    public ?string $peran = null;

    public int $urutan = 1;

    public bool $isActive = true;

    // ---------- Form gaya (style) ----------

    public bool $showStyleForm = false;

    public ?int $editingStyleId = null;

    public string $styleName = '';

    public string $styleDescription = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $this->styleId = GayaShotlist::bawaan()?->id;
    }

    public function pilihStyle(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $this->styleId = GayaShotlist::findOrFail($id)->id;
        $this->showForm = false;
    }

    public function createStyle(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $this->reset(['editingStyleId', 'styleName', 'styleDescription']);
        $this->resetErrorBag();
        $this->showStyleForm = true;
    }

    public function editStyle(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $s = GayaShotlist::findOrFail($id);
        $this->editingStyleId = $s->id;
        $this->styleName = $s->name;
        $this->styleDescription = $s->description ?? '';
        $this->resetErrorBag();
        $this->showStyleForm = true;
    }

    public function saveStyle(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);

        $v = $this->validate([
            'styleName' => ['required', 'string', 'max:100'],
            'styleDescription' => ['nullable', 'string', 'max:255'],
        ], attributes: ['styleName' => 'nama style', 'styleDescription' => 'deskripsi']);

        $s = GayaShotlist::updateOrCreate(['id' => $this->editingStyleId], [
            'name' => $v['styleName'],
            'description' => $v['styleDescription'] ?: null,
        ]);

        // Gaya pertama otomatis jadi default.
        if (! GayaShotlist::where('is_default', true)->exists()) {
            $s->update(['is_default' => true]);
        }

        $this->styleId = $s->id;
        $this->showStyleForm = false;
        $this->reset(['editingStyleId', 'styleName', 'styleDescription']);
        $this->dispatch('toast', message: 'Style shotlist disimpan.');
    }

    /** Jadikan gaya ini default studio (fallback episode tanpa seri / seri tanpa pilihan). */
    public function setDefaultStyle(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        GayaShotlist::where('is_default', true)->update(['is_default' => false]);
        GayaShotlist::findOrFail($id)->update(['is_default' => true]);
        $this->dispatch('toast', message: 'Style default diganti.');
    }

    public function deleteStyle(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $s = GayaShotlist::findOrFail($id);

        if ($s->is_default) {
            $this->dispatch('toast', message: 'Style default tidak bisa dihapus. Jadikan style lain default dulu.', icon: 'error');

            return;
        }
        if ($s->seri()->exists()) {
            $this->dispatch('toast', message: 'Style masih dipakai seri. Ganti style seri tersebut dulu.', icon: 'error');

            return;
        }

        $s->kolom()->get()->each->delete();
        $s->delete();
        if ($this->styleId === $id) {
            $this->styleId = GayaShotlist::bawaan()?->id;
        }
        $this->dispatch('toast', message: 'Style dihapus.');
    }

    public function cancelStyle(): void
    {
        $this->showStyleForm = false;
        $this->reset(['editingStyleId', 'styleName', 'styleDescription']);
        $this->resetErrorBag();
    }

    public function create(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        abort_unless($this->styleId !== null, 404);
        $this->reset(['editingId', 'label', 'opsiText', 'peran']);
        $this->tipe = 'text';
        $this->urutan = (int) (KolomShotlist::gaya($this->styleId)->max('urutan') + 1);
        $this->isActive = true;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        $k = KolomShotlist::gaya($this->styleId)->findOrFail($id);
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

        abort_unless($this->styleId !== null, 404);

        // Peran scene & shot_code hanya boleh satu kolom per gaya; duration boleh banyak
        // (mis. Duration VO, Duration Animate, Realtime Duration).
        if ($v['peran'] && $v['peran'] !== PeranKolomShotlist::DURATION->value) {
            $bentrok = KolomShotlist::gaya($this->styleId)->where('peran', $v['peran'])
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
            'style_id' => $this->styleId,
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
        $k = KolomShotlist::gaya($this->styleId)->findOrFail($id);
        $k->update(['is_active' => ! $k->is_active]);
    }

    public function delete(int $id): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
        KolomShotlist::gaya($this->styleId)->whereKey($id)->first()?->delete();
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
        while (KolomShotlist::gaya($this->styleId)->where('key', $key)->exists()) {
            $key = $base.'_'.(++$i);
        }

        return $key;
    }

    public function render()
    {
        return view('livewire.pengaturan.shotlist-kolom', [
            'styles' => GayaShotlist::withCount(['kolom', 'seri'])->orderByDesc('is_default')->orderBy('name')->get(),
            'styleAktif' => $this->styleId ? GayaShotlist::find($this->styleId) : null,
            'kolom' => KolomShotlist::gaya($this->styleId)->urut()->get(),
            'peranOpsi' => PeranKolomShotlist::options(),
        ]);
    }
}
