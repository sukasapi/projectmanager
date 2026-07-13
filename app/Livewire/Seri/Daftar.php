<?php

namespace App\Livewire\Seri;

use App\Enums\TaskStatus;
use App\Models\Adegan;
use App\Models\GayaShotlist;
use App\Models\Seri;
use App\Models\Shot;
use App\Models\TugasShot;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Seri / Judul — induk episode (Tier C6). Kelola seri + lihat episode & progres agregat.
 * Akses kelola: Supervisor/Super Admin (manage-tim).
 */
#[Layout('components.layouts.app')]
class Daftar extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    /** Style shotlist pilihan seri — kosong = pakai default studio. */
    public ?int $shotlistStyleId = null;

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-tim'), 403);
    }

    public function create(): void
    {
        abort_unless(Gate::allows('manage-tim'), 403);
        $this->reset(['editingId', 'name', 'description', 'shotlistStyleId']);
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless(Gate::allows('manage-tim'), 403);
        $s = Seri::findOrFail($id);
        $this->editingId = $s->id;
        $this->name = $s->name;
        $this->description = $s->description ?? '';
        $this->shotlistStyleId = $s->shotlist_style_id;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(Gate::allows('manage-tim'), 403);

        $v = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'shotlistStyleId' => ['nullable', 'integer', 'exists:kf_gaya_shotlist,id'],
        ], attributes: ['name' => 'nama seri', 'shotlistStyleId' => 'style shotlist']);

        Seri::updateOrCreate(['id' => $this->editingId], [
            'name' => $v['name'],
            'description' => $v['description'] ?: null,
            'shotlist_style_id' => $v['shotlistStyleId'] ?: null,
        ]);

        $this->reset(['editingId', 'name', 'description', 'shotlistStyleId']);
        $this->showForm = false;
        $this->dispatch('seri-tersimpan');
        $this->dispatch('toast', message: 'Seri disimpan.');
    }

    public function delete(int $id): void
    {
        abort_unless(Gate::allows('manage-tim'), 403);
        // Episode & aset yang tertaut akan di-set null (nullOnDelete) — tidak ikut terhapus.
        Seri::whereKey($id)->first()?->delete();
        $this->dispatch('toast', message: 'Seri dihapus.');
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'description', 'shotlistStyleId']);
        $this->showForm = false;
    }

    public function render()
    {
        $seri = Seri::with(['episode' => fn ($q) => $q->orderByDesc('id'), 'gayaShotlist'])->orderBy('name')->get();

        // Progres agregat per seri (approved shot-task ÷ total), sedikit query.
        $epBySeri = $seri->flatMap(fn ($s) => $s->episode->map(fn ($e) => ['sid' => $s->id, 'eid' => $e->id]));
        $sceneToEp = Adegan::whereIn('project_id', $epBySeri->pluck('eid'))->pluck('project_id', 'id');
        $shotToEp = Shot::whereIn('scene_id', $sceneToEp->keys())->pluck('scene_id', 'id')->map(fn ($sc) => $sceneToEp[$sc] ?? null);
        $epToSeri = $epBySeri->pluck('sid', 'eid');

        $tot = [];
        $done = [];
        foreach (TugasShot::whereIn('shot_id', $shotToEp->keys())->get(['shot_id', 'status']) as $t) {
            $sid = $epToSeri[$shotToEp[$t->shot_id] ?? null] ?? null;
            if (! $sid) {
                continue;
            }
            $tot[$sid] = ($tot[$sid] ?? 0) + 1;
            if ($t->status === TaskStatus::APPROVED) {
                $done[$sid] = ($done[$sid] ?? 0) + 1;
            }
        }

        $progres = collect($tot)->mapWithKeys(fn ($n, $sid) => [$sid => (int) round(($done[$sid] ?? 0) / max(1, $n) * 100)]);

        return view('livewire.seri.daftar', [
            'seri' => $seri,
            'progres' => $progres,
            'bisaKelola' => Gate::allows('manage-tim'),
            'daftarStyle' => GayaShotlist::orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'is_default']),
        ]);
    }
}
