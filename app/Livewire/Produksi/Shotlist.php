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
use App\Services\NineRouterService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/**
 * Shotlist per episode (petugas shotlist / pengelola). Isi manual, impor CSV, atau
 * generate dari skenario dengan AI (9Router), lalu "Generate ke Produksi" → membuat
 * Adegan + Shot + shot-task yang dikerjakan tim. Kolom mengikuti konfigurasi studio
 * (KolomShotlist). Lihat requirement Shotlist 2026-07 & docs/2026-07-09_shotlist-ai.md.
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

    /** Skenario (naskah) episode — sumber shotlist manual maupun AI. */
    public string $skenario = '';

    public bool $showAiForm = false;

    /** Perkiraan durasi episode (menit) untuk generate AI. */
    public ?int $estimasiMenit = null;

    /** Instruksi tambahan opsional untuk AI (mis. hal yang wajib dirinci di Detail Visual). */
    public string $instruksiAi = '';

    public function mount(): void
    {
        $episode = (int) request()->integer('episode');
        if ($episode && $this->bolehAkses($episode)) {
            $this->proyekId = $episode;
            $this->muatSkenario();
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

    /** Boleh mengisi skenario: pengelola episode ATAU petugas tahap Script/Shotlist. */
    private function bolehIsiSkenario(): bool
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
            ->whereHas('tahap', fn ($q) => $q->whereIn('code', ['script', 'shotlist']))
            ->exists();
    }

    public function pilihEpisode(int $id): void
    {
        if ($this->bolehAkses($id)) {
            $this->proyekId = $id;
            $this->muatSkenario();
        }
    }

    public function gantiEpisode(): void
    {
        $this->proyekId = null;
        $this->showForm = false;
        $this->showAiForm = false;
        $this->reset(['skenario', 'estimasiMenit']);
    }

    private function muatSkenario(): void
    {
        $this->skenario = (string) Proyek::find($this->proyekId)?->skenario;
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

    // ---------- Edit satu sel via modal (klik 2× pada sel tabel) ----------
    // Input di modal mengikuti tipe kolom master (KolomShotlist: text/number/select+opsi).

    public ?int $editCellId = null;

    public string $editCellKey = '';

    public string $editCellValue = '';

    public function mulaiEditSel(int $id, string $key): void
    {
        abort_unless($this->bolehKelola(), 403);
        abort_unless($this->kolom()->contains('key', $key), 404);

        $row = ShotlistRow::where('project_id', $this->proyekId)->findOrFail($id);
        $this->editCellId = $row->id;
        $this->editCellKey = $key;
        $this->editCellValue = (string) ($row->data[$key] ?? '');
    }

    /** Simpan nilai sel yang sedang diedit di modal (submit / Enter). */
    public function simpanSel(): void
    {
        // Abaikan bila tak ada sel aktif (mis. submit menyusul setelah batal).
        if (! $this->editCellId || $this->editCellKey === '') {
            return;
        }
        abort_unless($this->bolehKelola(), 403);

        $row = ShotlistRow::where('project_id', $this->proyekId)->whereKey($this->editCellId)->first();
        if ($row) {
            $data = $row->data ?? [];
            $data[$this->editCellKey] = trim($this->editCellValue);
            $row->update(['data' => $data]);
        }

        $this->reset(['editCellId', 'editCellKey', 'editCellValue']);
        $this->dispatch('toast', message: 'Sel disimpan.');
    }

    public function batalEditSel(): void
    {
        $this->reset(['editCellId', 'editCellKey', 'editCellValue']);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['editingId', 'baris']);
        $this->resetErrorBag();
    }

    public function simpanSkenario(): void
    {
        abort_unless($this->bolehIsiSkenario(), 403);
        $this->validate(['skenario' => ['nullable', 'string', 'max:60000']], attributes: ['skenario' => 'skenario']);

        Proyek::findOrFail($this->proyekId)->update(['skenario' => trim($this->skenario) ?: null]);
        $this->dispatch('toast', message: 'Skenario disimpan.');
    }

    /** Buka form generate AI — mensyaratkan skenario sudah tersimpan. */
    public function bukaFormAi(): void
    {
        abort_unless($this->bolehKelola(), 403);

        if (trim((string) Proyek::findOrFail($this->proyekId)->skenario) === '') {
            $this->dispatch('toast', message: 'Isi & simpan skenario dulu sebelum membuat shotlist dengan AI.', icon: 'error');

            return;
        }

        $this->resetErrorBag();
        $this->showAiForm = true;
    }

    public function tutupFormAi(): void
    {
        $this->showAiForm = false;
        $this->reset(['estimasiMenit', 'instruksiAi']);
        $this->resetErrorBag();
    }

    /**
     * Generate baris shotlist dari skenario via AI (9Router). Hasil DITAMBAHKAN
     * sebagai baris baru (tidak menghapus yang ada) agar bisa diperiksa/disunting
     * sebelum "Generate ke Produksi".
     */
    public function generateAi(NineRouterService $ai): void
    {
        abort_unless($this->bolehKelola(), 403);
        $this->validate(
            [
                'estimasiMenit' => ['required', 'integer', 'min:1', 'max:240'],
                'instruksiAi' => ['nullable', 'string', 'max:2000'],
            ],
            attributes: ['estimasiMenit' => 'perkiraan durasi', 'instruksiAi' => 'instruksi tambahan'],
        );

        $proyek = Proyek::findOrFail($this->proyekId);
        $skenario = trim((string) $proyek->skenario);
        if ($skenario === '') {
            $this->addError('ai', 'Skenario episode masih kosong — isi & simpan dulu.');

            return;
        }

        try {
            $rows = $ai->generateShotlist($skenario, $this->estimasiMenit * 60, $this->kolom(), trim($this->instruksiAi) ?: null);
        } catch (Throwable $e) {
            report($e);
            $this->addError('ai', 'Gagal generate shotlist: '.$e->getMessage());

            return;
        }

        $urut = (int) ShotlistRow::where('project_id', $proyek->id)->max('urutan');
        foreach ($rows as $data) {
            ShotlistRow::create(['project_id' => $proyek->id, 'urutan' => ++$urut, 'data' => $data]);
        }

        $this->tutupFormAi();
        $this->dispatch('toast', message: count($rows).' baris shotlist dihasilkan AI — periksa & sunting sebelum Generate ke Produksi.');
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

        // value() ikut cast model → bisa berupa enum TaskStatus; normalkan (string dari
        // driver tertentu tetap ditangani via tryFrom).
        $shotlistStatus = TugasTahap::where('project_id', $this->proyekId)
            ->whereHas('tahap', fn ($q) => $q->where('code', 'shotlist'))
            ->value('status');
        if (is_string($shotlistStatus)) {
            $shotlistStatus = TaskStatus::tryFrom($shotlistStatus);
        }

        return view('livewire.produksi.shotlist', [
            'proyek' => Proyek::findOrFail($this->proyekId),
            'bisaKelola' => $this->bolehKelola(),
            'bisaIsiSkenario' => $this->bolehIsiSkenario(),
            'aiAktif' => app(NineRouterService::class)->aktif(),
            'kolom' => $this->kolom(),
            'rows' => ShotlistRow::where('project_id', $this->proyekId)->orderBy('urutan')->get(),
            'belumDigenerate' => ShotlistRow::where('project_id', $this->proyekId)->whereNull('shot_id')->count(),
            // Status tahap Shotlist (pra-produksi) untuk isyarat: generate idealnya setelah disetujui.
            'shotlistDisetujui' => $shotlistStatus === TaskStatus::APPROVED,
            'shotlistStatusLabel' => $shotlistStatus?->label(),
        ]);
    }
}
