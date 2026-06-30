<?php

namespace App\Livewire;

use App\Actions\CreateShot;
use App\Actions\TransitionShotTaskStatus;
use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use App\Enums\TaskStatus;
use App\Exceptions\InvalidShotTaskTransition;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\Tahap;
use App\Models\TugasShot;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ShotMatrix extends Component
{
    public ?int $proyekId = null;

    /** Form tambah shot (modal). Kode shot di-generate otomatis. */
    public bool $showShotForm = false;

    public ?int $newSceneId = null;

    public string $newShotCode = '';

    public int $newDurationSeconds = 0;

    /** Form tambah scene (modal). */
    public bool $showSceneForm = false;

    public string $newSceneName = '';

    /** Assign massal (modal): tugaskan artis ke banyak shot pada satu tahap. */
    public bool $showBulk = false;

    public ?int $bulkTahapId = null;

    public ?int $bulkSceneId = null; // null = semua scene

    public array $bulkArtisIds = [];

    public string $bulkMode = 'tambah'; // tambah | ganti

    public function mount(): void
    {
        // Deep-link opsional: /shot-matrix?episode=ID langsung membuka matriks episode itu.
        $episode = (int) request()->integer('episode');
        if ($episode && $this->bolehAkses($episode)) {
            $this->proyekId = $episode;
        }
    }

    /** Supervisor melihat semua episode; lainnya hanya yang ditugaskan padanya. */
    private function bolehAkses(int $proyekId): bool
    {
        return Gate::allows('manage-tim')
            || Proyek::whereKey($proyekId)->untukUser(auth()->id() ?? 0)->exists();
    }

    /** Setup (tambah/hapus scene & shot): Super Admin / Supervisor / Team Lead episode ini. */
    private function bolehKelola(): bool
    {
        return $this->proyekId
            && Proyek::find($this->proyekId)?->dapatDikelola(auth()->user());
    }

    public function bukaSceneForm(): void
    {
        abort_unless($this->bolehKelola(), 403);
        $this->newSceneName = '';
        $this->resetErrorBag();
        $this->showSceneForm = true;
    }

    public function tutupSceneForm(): void
    {
        $this->showSceneForm = false;
    }

    /** Tambah scene baru pada episode terpilih. */
    public function addScene(): void
    {
        abort_unless($this->bolehKelola(), 403);

        $validated = $this->validate([
            'newSceneName' => [
                'required', 'string', 'max:255',
                Rule::unique('kf_adegan', 'scene_name')->where(fn ($q) => $q->where('project_id', $this->proyekId)),
            ],
        ], attributes: ['newSceneName' => 'nama scene']);

        Adegan::create([
            'project_id' => $this->proyekId,
            'scene_name' => $validated['newSceneName'],
        ]);

        $this->showSceneForm = false;
        $this->reset('newSceneName');
        $this->dispatch('shot-tersimpan');
        $this->dispatch('toast', message: 'Scene ditambahkan.');
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
    }

    public function bukaShotForm(): void
    {
        abort_unless($this->bolehKelola(), 403);

        $this->reset(['newShotCode', 'newDurationSeconds']);
        $this->newSceneId = Adegan::where('project_id', $this->proyekId)->orderByRaw('LENGTH(scene_name), scene_name')->value('id');
        $this->newShotCode = $this->newSceneId ? $this->generateShotCode($this->newSceneId) : '';
        $this->resetErrorBag();
        $this->showShotForm = true;
    }

    public function tutupShotForm(): void
    {
        $this->showShotForm = false;
    }

    /** Saat scene dipilih, kode shot diperbarui otomatis. */
    public function updatedNewSceneId($value): void
    {
        $this->newShotCode = $value ? $this->generateShotCode((int) $value) : '';
    }

    /** Format kode: SC{nomor scene}_SH{nomor urut berikutnya pada scene tsb}. */
    private function generateShotCode(int $sceneId): string
    {
        $scene = Adegan::find($sceneId);
        if (! $scene) {
            return '';
        }

        // Nomor scene: ambil angka dari nama ("Scene 05" → 05), fallback ke urutan.
        if (preg_match('/\d+/', $scene->scene_name, $m)) {
            $scNum = (int) $m[0];
        } else {
            $scNum = Adegan::where('project_id', $scene->project_id)->where('id', '<=', $sceneId)->count();
        }

        // Nomor shot berikutnya: max SH pada scene + 1.
        $maks = 0;
        foreach (Shot::where('scene_id', $sceneId)->pluck('shot_code') as $kode) {
            if (preg_match('/SH(\d+)/i', (string) $kode, $mm)) {
                $maks = max($maks, (int) $mm[1]);
            }
        }

        return sprintf('SC%02d_SH%02d', $scNum, $maks + 1);
    }

    /**
     * Tambah shot baru. Kode shot di-generate otomatis. total_duration via ShotObserver.
     */
    public function addShot(CreateShot $createShot): void
    {
        abort_unless($this->bolehKelola(), 403);

        $this->validate([
            'newSceneId' => ['required', 'integer', Rule::exists('kf_adegan', 'id')->where(fn ($q) => $q->where('project_id', $this->proyekId))],
            'newDurationSeconds' => ['required', 'integer', 'min:0', 'max:86400'],
        ], attributes: [
            'newSceneId' => 'scene',
            'newDurationSeconds' => 'durasi',
        ]);

        // Generate ulang saat simpan agar nomor selalu urut & unik.
        $this->newShotCode = $this->generateShotCode($this->newSceneId);

        $createShot->handle([
            'scene_id' => $this->newSceneId,
            'shot_code' => $this->newShotCode,
            'duration_seconds' => $this->newDurationSeconds,
        ]);

        $kode = $this->newShotCode;
        $this->showShotForm = false;
        $this->reset(['newShotCode', 'newDurationSeconds']);
        $this->dispatch('shot-tersimpan');
        $this->dispatch('toast', message: "Shot {$kode} ditambahkan.");
    }

    /** Menyegar matriks setelah aksi di panel review. */
    #[On('shot-tersimpan')]
    public function refreshMatrix(): void
    {
        // Tubuh kosong: cukup memicu render ulang komponen.
    }

    public function deleteShot(int $shotId): void
    {
        abort_unless($this->bolehKelola(), 403);
        Shot::whereKey($shotId)->first()?->delete();
        $this->dispatch('shot-tersimpan');
    }

    public function bukaBulk(): void
    {
        abort_unless($this->bolehKelola(), 403);

        $this->bulkTahapId = Tahap::milikEpisode((int) $this->proyekId)
            ->aktif()->fase(FaseProduksi::PRODUKSI)->where('level', LevelTahap::SHOT->value)->urut()->value('id');
        $this->bulkSceneId = null;
        $this->bulkArtisIds = [];
        $this->bulkMode = 'tambah';
        $this->resetErrorBag();
        $this->showBulk = true;
    }

    public function tutupBulk(): void
    {
        $this->showBulk = false;
    }

    /** Tugaskan artis terpilih ke seluruh shot-task pada tahap & lingkup yang dipilih. */
    public function assignMassal(): void
    {
        abort_unless($this->bolehKelola(), 403);

        $this->validate([
            'bulkTahapId' => ['required', 'integer', Rule::exists('kf_tahap', 'id')->where(fn ($q) => $q->where('project_id', $this->proyekId))],
            'bulkSceneId' => ['nullable', 'integer', Rule::exists('kf_adegan', 'id')->where(fn ($q) => $q->where('project_id', $this->proyekId))],
            'bulkArtisIds' => ['array', 'min:1'],
            'bulkArtisIds.*' => ['integer', 'exists:kf_pengguna,id'],
        ], attributes: [
            'bulkTahapId' => 'tahap',
            'bulkArtisIds' => 'artis',
        ]);

        $sceneIds = $this->bulkSceneId
            ? [$this->bulkSceneId]
            : Adegan::where('project_id', $this->proyekId)->pluck('id')->all();
        $shotIds = Shot::whereIn('scene_id', $sceneIds)->pluck('id');
        $tugas = TugasShot::whereIn('shot_id', $shotIds)->where('tahap_id', $this->bulkTahapId)->get();

        foreach ($tugas as $t) {
            $this->bulkMode === 'ganti'
                ? $t->artists()->sync($this->bulkArtisIds)
                : $t->artists()->syncWithoutDetaching($this->bulkArtisIds);
        }

        $jumlah = $tugas->count();
        $this->showBulk = false;
        $this->reset(['bulkSceneId', 'bulkArtisIds']);
        $this->dispatch('shot-tersimpan');
        $this->dispatch('toast', message: "Penugasan massal diterapkan ke {$jumlah} shot-task.");
    }

    /**
     * Buka panel review untuk satu shot-task (ditangani komponen ReviewPanel — Langkah 4).
     */
    public function review(int $tugasShotId): void
    {
        $this->dispatch('buka-review', tugasShotId: $tugasShotId);
    }

    /**
     * Aksi cepat "Mulai" langsung dari sel matriks (tanpa membuka panel).
     * Hanya artis yang ditugaskan atau reviewer, & episode sudah dipublish.
     */
    public function mulaiShot(int $tugasShotId, TransitionShotTaskStatus $transition): void
    {
        $task = TugasShot::with(['shot.adegan.proyek', 'artists'])->find($tugasShotId);
        $proyek = $task?->shot?->adegan?->proyek;

        $bisa = $task && $proyek && $proyek->isPublished()
            && ($task->artists->contains(auth()->id()) || $proyek->dapatReview(auth()->user()));
        abort_unless($bisa, 403);

        if ($task->status === TaskStatus::NOT_STARTED) {
            try {
                $transition->handle($task, TaskStatus::IN_PROGRESS, auth()->user(), null);
                $this->dispatch('toast', message: "Mulai mengerjakan {$task->shot?->shot_code}.");
            } catch (InvalidShotTaskTransition $e) {
                // Mis. tahap prasyarat belum disetujui.
                $this->dispatch('toast', message: $e->getMessage(), icon: 'error');

                return;
            }
        }

        $this->dispatch('shot-tersimpan');
    }

    public function render()
    {
        $supervisor = Gate::allows('manage-tim');

        // Verifikasi akses bila ada episode terpilih.
        if ($this->proyekId && ! $this->bolehAkses($this->proyekId)) {
            $this->proyekId = null;
        }

        // Kolom = snapshot pipeline episode terpilih (bukan template global).
        $tahapKolom = $this->proyekId
            ? Tahap::milikEpisode($this->proyekId)
                ->aktif()
                ->fase(FaseProduksi::PRODUKSI)
                ->where('level', LevelTahap::SHOT->value)
                ->urut()
                ->get()
            : collect();

        $proyek = $this->proyekId
            ? Proyek::with([
                'adegan' => fn ($q) => $q->orderByRaw('LENGTH(scene_name), scene_name'),
                // Urut berdasarkan NAMA shot (natural: SH2 sebelum SH10), bukan durasi.
                'adegan.shot' => fn ($q) => $q->orderByRaw('LENGTH(shot_code), shot_code'),
                'adegan.shot.tugasShot.artists',
                'adegan.shot.tugasShot.tahap',
            ])->find($this->proyekId)
            : null;

        // Kartu pemilih episode (portofolio). Non-supervisor hanya episode miliknya.
        $episodes = $this->proyekId ? collect() : Proyek::query()
            ->with('klien')
            ->withCount('adegan')
            ->withSum('adegan as total_durasi', 'total_duration')
            ->when(! $supervisor, fn ($q) => $q->untukUser(auth()->id() ?? 0))
            ->orderByDesc('id')
            ->get();

        return view('livewire.shot-matrix', [
            'proyek' => $proyek,
            'tahapKolom' => $tahapKolom,
            'episodes' => $episodes,
            'dapatKelola' => $proyek ? $proyek->dapatDikelola(auth()->user()) : false,
            'daftarAdegan' => $this->proyekId
                ? Adegan::where('project_id', $this->proyekId)->orderByRaw('LENGTH(scene_name), scene_name')->get(['id', 'scene_name'])
                : collect(),
            'daftarArtis' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
            // Pengelola episode (Supervisor/Super Admin atau Team Lead) boleh membuka semua sel;
            // anggota biasa hanya sel (shot-task) yang ditugaskan padanya.
            'kelolaEpisode' => $proyek ? ($supervisor || $proyek->team_lead_id === auth()->id()) : false,
            'bisaReviewEpisode' => $proyek ? $proyek->dapatReview(auth()->user()) : false,
            'published' => $proyek ? $proyek->isPublished() : false,
            'uid' => auth()->id(),
        ]);
    }
}
