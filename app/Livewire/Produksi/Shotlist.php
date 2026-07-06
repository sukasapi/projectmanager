<?php

namespace App\Livewire\Produksi;

use App\Actions\CreateShot;
use App\Enums\PeranKolomShotlist;
use App\Enums\TaskStatus;
use App\Models\Adegan;
use App\Models\KolomShotlist;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\Shotlist as ShotlistRow;
use App\Models\TugasTahap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Shotlist per episode (petugas shotlist / pengelola). Isi manual atau impor CSV,
 * lalu "Generate ke Produksi" → membuat Adegan + Shot + shot-task yang dikerjakan tim.
 * Kolom mengikuti konfigurasi studio (KolomShotlist). Lihat requirement Shotlist 2026-07.
 */
#[Layout('components.layouts.app')]
class Shotlist extends Component
{
    use WithFileUploads;

    public ?int $proyekId = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    /** Nilai baris yang sedang diedit, dikunci per key kolom. */
    public array $baris = [];

    /** Berkas CSV untuk impor. */
    public $csv;

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

    /** Boleh mengelola shotlist: pengelola episode ATAU petugas shotlist (artis tahap 'shotlist'). */
    private function bolehKelola(): bool
    {
        $proyek = $this->proyekId ? Proyek::find($this->proyekId) : null;
        if (! $proyek) {
            return false;
        }
        if ($proyek->dapatDikelola(auth()->user())) {
            return true;
        }

        return TugasTahap::where('project_id', $proyek->id)
            ->where('artist_id', auth()->id())
            ->whereHas('tahap', fn ($q) => $q->where('code', 'shotlist'))
            ->exists();
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

    /** @return Collection<int, KolomShotlist> */
    private function kolom()
    {
        return KolomShotlist::aktif()->urut()->get();
    }

    public function tambah(): void
    {
        abort_unless($this->bolehKelola(), 403);
        $this->editingId = null;
        $this->baris = $this->kolom()->mapWithKeys(fn ($k) => [$k->key => ''])->all();
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->bolehKelola(), 403);
        $row = ShotlistRow::where('project_id', $this->proyekId)->findOrFail($id);
        $this->editingId = $row->id;
        $this->baris = array_merge($this->kolom()->mapWithKeys(fn ($k) => [$k->key => ''])->all(), $row->data ?? []);
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->bolehKelola(), 403);

        // Hanya simpan key kolom yang dikenal (buang input asing).
        $data = collect($this->kolom())->mapWithKeys(fn ($k) => [$k->key => trim((string) ($this->baris[$k->key] ?? ''))])->all();

        if ($this->editingId) {
            ShotlistRow::where('project_id', $this->proyekId)->whereKey($this->editingId)->first()?->update(['data' => $data]);
        } else {
            $urut = (int) ShotlistRow::where('project_id', $this->proyekId)->max('urutan');
            ShotlistRow::create(['project_id' => $this->proyekId, 'urutan' => $urut + 1, 'data' => $data]);
        }

        $this->showForm = false;
        $this->reset(['editingId', 'baris']);
        $this->dispatch('toast', message: 'Baris shotlist disimpan.');
    }

    public function hapus(int $id): void
    {
        abort_unless($this->bolehKelola(), 403);
        ShotlistRow::where('project_id', $this->proyekId)->whereKey($id)->first()?->delete();
        $this->dispatch('toast', message: 'Baris dihapus.');
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['editingId', 'baris']);
        $this->resetErrorBag();
    }

    /** Impor baris dari berkas CSV; header dicocokkan ke label/kunci kolom. */
    public function importCsv(): void
    {
        abort_unless($this->bolehKelola(), 403);
        $this->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:4096']], attributes: ['csv' => 'berkas CSV']);

        $lines = file($this->csv->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        if (count($lines) < 2) {
            $this->addError('csv', 'CSV tidak berisi data (butuh baris header + minimal 1 baris).');

            return;
        }

        // Deteksi pemisah dari baris header (koma vs titik-koma).
        $delim = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';
        $header = array_map('trim', str_getcsv(array_shift($lines), $delim));

        // Petakan indeks header → key kolom (cocokkan label atau key, case-insensitive).
        $kolom = $this->kolom();
        $map = [];
        foreach ($header as $i => $h) {
            $norm = Str::lower(trim($h));
            $col = $kolom->first(fn ($c) => Str::lower($c->label) === $norm || $c->key === $norm);
            if ($col) {
                $map[$i] = $col->key;
            }
        }

        if (empty($map)) {
            $this->addError('csv', 'Tidak ada header CSV yang cocok dengan kolom shotlist. Sesuaikan judul kolom atau konfigurasi kolom.');

            return;
        }

        $urut = (int) ShotlistRow::where('project_id', $this->proyekId)->max('urutan');
        $jumlah = 0;
        foreach ($lines as $line) {
            $sel = str_getcsv($line, $delim);
            $data = [];
            foreach ($map as $i => $key) {
                $data[$key] = trim((string) ($sel[$i] ?? ''));
            }
            if (count(array_filter($data, fn ($v) => $v !== '')) === 0) {
                continue;
            }
            ShotlistRow::create(['project_id' => $this->proyekId, 'urutan' => ++$urut, 'data' => $data]);
            $jumlah++;
        }

        $this->reset('csv');
        $this->dispatch('toast', message: "{$jumlah} baris shotlist diimpor.");
    }

    /** Unduh template CSV dengan header sesuai kolom studio (untuk diisi lalu diimpor). */
    public function unduhTemplate()
    {
        abort_unless($this->proyekId && $this->bolehKelola(), 403);
        $labels = $this->kolom()->pluck('label')->all();

        return response()->streamDownload(function () use ($labels) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $labels);
            fclose($out);
        }, 'template-shotlist.csv', ['Content-Type' => 'text/csv']);
    }

    /** Generate baris shotlist (yang belum) menjadi Adegan + Shot + shot-task di Produksi. */
    public function generate(): void
    {
        abort_unless($this->bolehKelola(), 403);
        $proyek = Proyek::findOrFail($this->proyekId);

        $sceneKey = KolomShotlist::keyBerperan(PeranKolomShotlist::SCENE);
        $codeKey = KolomShotlist::keyBerperan(PeranKolomShotlist::SHOT_CODE);
        $durKey = KolomShotlist::keyBerperan(PeranKolomShotlist::DURATION);

        if (! $sceneKey || ! $codeKey) {
            $this->dispatch('toast', message: 'Tandai dulu kolom Scene & Shot No# di Konfigurasi Kolom Shotlist.', icon: 'error');

            return;
        }

        $peranKeys = array_filter([$sceneKey, $codeKey, $durKey]);
        $rows = ShotlistRow::where('project_id', $proyek->id)->whereNull('shot_id')->orderBy('urutan')->get();
        $jumlah = 0;

        foreach ($rows as $row) {
            $data = $row->data ?? [];
            $sceneName = trim((string) ($data[$sceneKey] ?? '')) ?: 'Scene 01';
            $shotCode = trim((string) ($data[$codeKey] ?? '')) ?: 'SH'.$row->id;
            $durasi = (int) ($durKey ? ($data[$durKey] ?? 0) : 0);

            $adegan = Adegan::firstOrCreate(['project_id' => $proyek->id, 'scene_name' => $sceneName]);

            $shot = Shot::where('scene_id', $adegan->id)->where('shot_code', $shotCode)->first();
            if (! $shot) {
                $shot = app(CreateShot::class)->handle([
                    'scene_id' => $adegan->id,
                    'shot_code' => $shotCode,
                    'duration_seconds' => $durasi,
                ]);
            }

            // Metadata = seluruh kolom NON-peran (VO, Visual, Type of Shot, dll).
            $meta = collect($data)->except($peranKeys)->filter(fn ($v) => $v !== null && $v !== '')->all();
            $shot->update(['meta' => $meta ?: null]);

            $row->update(['shot_id' => $shot->id]);
            $jumlah++;
        }

        $this->dispatch('toast', message: $jumlah > 0 ? "{$jumlah} shot dibuat di Produksi." : 'Tidak ada baris baru untuk di-generate.');
    }

    public function render()
    {
        if (! $this->proyekId) {
            $supervisor = Gate::allows('manage-tim');
            $episodes = Proyek::query()
                ->when(! $supervisor, fn ($q) => $q->untukUser(auth()->id() ?? 0))
                ->orderByDesc('id')->get();

            return view('livewire.produksi.shotlist', ['episodes' => $episodes]);
        }

        $shotlistStatus = TugasTahap::where('project_id', $this->proyekId)
            ->whereHas('tahap', fn ($q) => $q->where('code', 'shotlist'))
            ->value('status');

        return view('livewire.produksi.shotlist', [
            'proyek' => Proyek::findOrFail($this->proyekId),
            'bisaKelola' => $this->bolehKelola(),
            'kolom' => $this->kolom(),
            'rows' => ShotlistRow::where('project_id', $this->proyekId)->orderBy('urutan')->get(),
            'belumDigenerate' => ShotlistRow::where('project_id', $this->proyekId)->whereNull('shot_id')->count(),
            // Status tahap Shotlist (pra-produksi) untuk isyarat: generate idealnya setelah disetujui.
            'shotlistDisetujui' => $shotlistStatus === TaskStatus::APPROVED->value,
            'shotlistStatusLabel' => $shotlistStatus ? TaskStatus::from($shotlistStatus)->label() : null,
        ]);
    }
}
