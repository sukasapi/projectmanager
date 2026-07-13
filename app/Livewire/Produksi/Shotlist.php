<?php

namespace App\Livewire\Produksi;

use App\Actions\CreateShot;
use App\Enums\PeranKolomShotlist;
use App\Enums\TaskStatus;
use App\Models\Adegan;
use App\Models\GayaShotlist;
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

    /** Perkiraan durasi episode (menit) untuk generate AI — kosong = AI menentukan sendiri. */
    public ?int $estimasiMenit = null;

    /** Instruksi default untuk AI — gaya Realistic Cinematic + aturan strict kolom shotlist. */
    public const INSTRUKSI_AI_DEFAULT = <<<'TEKS'
Peran: Bertindaklah sebagai Storyboard Director dan Lead Animator profesional yang berspesialisasi dalam produksi animasi 3D bergaya Realistic Cinematic (bukan kartun, fokus pada pencahayaan dramatis, bayangan kuat, dan tekstur detail).

Tugas: Buatlah Shot List lengkap dalam bentuk tabel berdasarkan naskah Skenario Detail dan teks VO (Voice Over) beserta pembagian kode PT- (Potongan Teks) yang diberikan oleh pengguna.

Aturan Teknis & Struktur Tabel (Strict):
1. Format Tabel wajib memiliki kolom tepat: Scene, Shot No#, Location, Dur. VO (s), Dur. Anim (s), Realtime Dur (s), Size, Angle, Movement, Direction, VO, Visual, Detail (1. Detail Visual, 2. Acting, 3. Kamera), dan Karakter.
2. Pilihan Teknis Kolom:
   - Size: ELS, LS, FS, WS, MS, MCU, CU, ECU, OSS.
   - Angle: EL (Eye Level), HA (High Angle), LA (Low Angle).
   - Movement: Still, Pan, Track, Zoom, Crab, Pedestal, Tilt, Arc, Handheld, Crane.
   - Direction: Up, Down, In, Out, Left, Right, Static, Follow Character, Shake, Rack Focus, Creep In.

Aturan Penulisan Konten (Strict):
1. Kolom Location: Murni hanya menunjukkan setting tempat/ruang geografis adegan berlangsung (Contoh: "Bait Allah", "Bukit Zaitun", "Ruang Pengadilan"). Dilarang menuliskan nama objek, wajah detail, atau nama karakter di kolom ini.
2. Kolom VO: Teks kalimat VO hanya boleh ditulis SATU KALI pada shot pertama saat kalimat tersebut mulai diucapkan berdasarkan blok PT-. Pada shot-shot kelanjutannya yang masih menggunakan durasi PT yang sama, kolom VO wajib DIKOSONGKAN (cukup diisi spasi/blank).
3. Kolom Detail: Wajib dipecah secara eksplisit menjadi 3 poin mandiri dengan bahasa deskriptif yang sederhana, lugas, mudah dipahami animator lapangan, dan hindari istilah rumit (seperti "kosmik", "apokaliptik", dll). Formatnya:
   - "1. Detail Visual: [Jelaskan posisi FG/BG, properti, lingkungan, dan pencahayaan secara sederhana]"
   - "2. Acting/Gesture: [Jelaskan gerakan tubuh, arah pandangan mata, dan ekspresi mikro karakter]"
   - "3. Kamera: [Jelaskan pergerakan lensa, efek buram latar belakang/Shallow DOF, atau transisi fokus]"
4. Sinkronisasi Durasi: Maksimalkan durasi hingga batas aman produksi (maksimum 10 detik per shot). Jika adegan membutuhkan jeda visual tanpa suara VO, isi Dur. VO dengan angka 0, namun berikan Dur. Anim yang cukup agar visualnya tetap mengalir.
5. Sensor Karakter Utama (Strict): Pastikan dalam seluruh deskripsi visual, adegan didesain agar WAJAH ISA MUTLAK TIDAK TERLIHAT SAMA SEKALI. Gunakan teknik kreatif seperti siluet membelakangi kamera, dari balik punggung (OSS ekstrem), sorot makro tangan/kaki, atau pantulan bayangan.

Format Output: Masukkkan langsung sebagai shotlist.
TEKS;

    /** Instruksi tambahan untuk AI — terisi default, boleh disunting petugas. */
    public string $instruksiAi = self::INSTRUKSI_AI_DEFAULT;

    /** Mode tampilan daftar shotlist: 'tree' (Scene → VO → Shot) atau 'tabel'. */
    public string $tampilan = 'tree';

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
        $this->showSkenarioModal = false;
        $this->showImporModal = false;
        $this->reset(['skenario', 'estimasiMenit']);
    }

    private function muatSkenario(): void
    {
        $this->skenario = (string) Proyek::find($this->proyekId)?->skenario;
    }

    /** ID gaya shotlist yang berlaku untuk episode ini (dari seri, fallback default). */
    private function styleId(): ?int
    {
        return GayaShotlist::untukProyek($this->proyekId ? Proyek::with('seri')->find($this->proyekId) : null)?->id;
    }

    /** @return Collection<int, KolomShotlist> kolom aktif milik gaya episode ini */
    private function kolom()
    {
        return KolomShotlist::aktif()->gaya($this->styleId())->urut()->get();
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

    // ---------- Modal Skenario & Impor CSV (kartu ringkas satu baris) ----------

    public bool $showSkenarioModal = false;

    public bool $showImporModal = false;

    public function bukaSkenario(): void
    {
        abort_unless($this->bolehIsiSkenario(), 403);
        $this->muatSkenario();
        $this->resetErrorBag('skenario');
        $this->showSkenarioModal = true;
    }

    public function tutupSkenario(): void
    {
        $this->showSkenarioModal = false;
        $this->muatSkenario();
        $this->resetErrorBag('skenario');
    }

    public function bukaImpor(): void
    {
        abort_unless($this->bolehKelola(), 403);
        $this->reset('csv');
        $this->resetErrorBag('csv');
        $this->showImporModal = true;
    }

    public function tutupImpor(): void
    {
        $this->showImporModal = false;
        $this->reset('csv');
        $this->resetErrorBag('csv');
    }

    public function simpanSkenario(): void
    {
        abort_unless($this->bolehIsiSkenario(), 403);
        $this->validate(['skenario' => ['nullable', 'string', 'max:60000']], attributes: ['skenario' => 'skenario']);

        Proyek::findOrFail($this->proyekId)->update(['skenario' => trim($this->skenario) ?: null]);
        $this->showSkenarioModal = false;
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

    /** Kembalikan instruksi AI ke teks bawaan studio. */
    public function resetInstruksiAi(): void
    {
        $this->instruksiAi = self::INSTRUKSI_AI_DEFAULT;
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
                'estimasiMenit' => ['nullable', 'integer', 'min:1', 'max:240'],
                'instruksiAi' => ['nullable', 'string', 'max:8000'],
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
            $rows = $ai->generateShotlist($skenario, $this->estimasiMenit ? $this->estimasiMenit * 60 : null, $this->kolom(), trim($this->instruksiAi) ?: null);
        } catch (Throwable $e) {
            report($e);
            $this->addError('ai', 'Gagal generate shotlist: '.$e->getMessage());

            return;
        }

        // Hasil AI MENGGANTIKAN shotlist lama — kosongkan dulu agar tidak menumpuk
        // (scene berulang di tampilan tree). Baris lama di-soft delete.
        ShotlistRow::where('project_id', $proyek->id)->get()->each->delete();

        $urut = 0;
        foreach ($rows as $data) {
            ShotlistRow::create(['project_id' => $proyek->id, 'urutan' => ++$urut, 'data' => $data]);
        }

        $this->tutupFormAi();
        $this->dispatch('toast', message: count($rows).' baris shotlist dihasilkan AI (shotlist lama dikosongkan) — periksa & sunting sebelum Generate ke Produksi.');
    }

    /** Hasil parse CSV yang menunggu konfirmasi (belum disimpan ke shotlist). */
    public array $pratinjauImpor = [];

    public bool $showPratinjauImpor = false;

    /**
     * Impor baris dari berkas CSV; header dicocokkan ke label/kunci kolom.
     * Hasil TIDAK langsung disimpan — ditampung di pratinjau, menunggu tombol
     * "Jadikan Shotlist" (konfirmasiImpor) atau "Batal" (batalImpor).
     */
    public function importCsv(): void
    {
        abort_unless($this->bolehKelola(), 403);
        $this->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:4096']], attributes: ['csv' => 'berkas CSV']);

        $isi = (string) file_get_contents($this->csv->getRealPath());

        // Normalkan encoding ke UTF-8 — CSV dari Excel Windows umumnya ANSI/Windows-1252
        // (memicu "Malformed UTF-8 characters" saat Livewire men-JSON-kan pratinjau).
        if (! mb_check_encoding($isi, 'UTF-8')) {
            $deteksi = mb_detect_encoding($isi, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true) ?: 'Windows-1252';
            $isi = mb_convert_encoding($isi, 'UTF-8', $deteksi);
        }
        // Buang BOM UTF-8 bila ada agar header kolom pertama cocok.
        $isi = ltrim($isi, "\u{FEFF}");

        $lines = preg_split('/\r\n|\r|\n/', $isi, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($lines) < 2) {
            $this->addError('csv', 'CSV tidak berisi data (butuh baris header + minimal 1 baris).');
            $this->dispatch('shotlist-impor-selesai', sukses: false);

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
            $this->dispatch('shotlist-impor-selesai', sukses: false);

            return;
        }

        $pratinjau = [];
        foreach ($lines as $line) {
            $sel = str_getcsv($line, $delim);
            $data = [];
            foreach ($map as $i => $key) {
                $data[$key] = trim((string) ($sel[$i] ?? ''));
            }
            if (count(array_filter($data, fn ($v) => $v !== '')) === 0) {
                continue;
            }
            $pratinjau[] = $data;
        }

        if (empty($pratinjau)) {
            $this->addError('csv', 'Semua baris CSV kosong — tidak ada yang bisa diimpor.');
            $this->dispatch('shotlist-impor-selesai', sukses: false);

            return;
        }

        $this->reset('csv');
        $this->showImporModal = false;
        $this->pratinjauImpor = $pratinjau;
        $this->showPratinjauImpor = true;
        $this->dispatch('shotlist-impor-selesai', sukses: true, jumlah: count($pratinjau));
    }

    /** Simpan hasil pratinjau impor sebagai baris shotlist — MENGGANTIKAN shotlist lama. */
    public function konfirmasiImpor(): void
    {
        abort_unless($this->bolehKelola(), 403);
        if (empty($this->pratinjauImpor)) {
            $this->batalImpor();

            return;
        }

        // Sama seperti generate AI: kosongkan dulu agar tidak menumpuk (soft delete).
        ShotlistRow::where('project_id', $this->proyekId)->get()->each->delete();

        $urut = 0;
        $jumlah = 0;
        foreach ($this->pratinjauImpor as $data) {
            ShotlistRow::create(['project_id' => $this->proyekId, 'urutan' => ++$urut, 'data' => $data]);
            $jumlah++;
        }

        $this->batalImpor();
        $this->dispatch('toast', message: "{$jumlah} baris shotlist diimpor (shotlist lama dikosongkan).");
    }

    /** Buang hasil pratinjau impor tanpa menyimpan. */
    public function batalImpor(): void
    {
        $this->reset(['pratinjauImpor', 'showPratinjauImpor']);
    }

    /**
     * Unduh template CSV dengan header sesuai kolom studio (untuk diisi lalu diimpor).
     * $delimiter: 'koma' (,) atau 'titik-koma' (;) — Excel lokal ID umumnya memakai ;.
     */
    public function unduhTemplate(string $delimiter = 'koma')
    {
        abort_unless($this->proyekId && $this->bolehKelola(), 403);
        $pemisah = $delimiter === 'titik-koma' ? ';' : ',';
        $labels = $this->kolom()->pluck('label')->all();

        return response()->streamDownload(function () use ($labels, $pemisah) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $labels, $pemisah);
            fclose($out);
        }, 'template-shotlist.csv', ['Content-Type' => 'text/csv']);
    }

    /** Generate baris shotlist (yang belum) menjadi Adegan + Shot + shot-task di Produksi. */
    public function generate(): void
    {
        abort_unless($this->bolehKelola(), 403);
        $proyek = Proyek::findOrFail($this->proyekId);

        $styleId = $this->styleId();
        $sceneKey = KolomShotlist::keyBerperan(PeranKolomShotlist::SCENE, $styleId);
        $codeKey = KolomShotlist::keyBerperan(PeranKolomShotlist::SHOT_CODE, $styleId);
        $durKey = KolomShotlist::keyBerperan(PeranKolomShotlist::DURATION, $styleId);

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

        $this->dispatch('toast', message: $jumlah > 0 ? "{$jumlah} shot dibuat di Produksi — lihat hasilnya di Shot Matrix." : 'Tidak ada baris baru untuk di-generate.');
    }

    public function gantiTampilan(string $mode): void
    {
        $this->tampilan = in_array($mode, ['tree', 'tabel'], true) ? $mode : 'tree';
    }

    /**
     * Kunci kolom istimewa untuk tampilan tree: scene & durasi (dari peran),
     * kode shot (peran), dan VO (deteksi dari key/label karena tak ada perannya).
     *
     * @return array{scene: ?string, code: ?string, dur: ?string, vo: ?string}
     */
    private function kunciTree(): array
    {
        $styleId = $this->styleId();

        return [
            'scene' => KolomShotlist::keyBerperan(PeranKolomShotlist::SCENE, $styleId),
            'code' => KolomShotlist::keyBerperan(PeranKolomShotlist::SHOT_CODE, $styleId),
            'dur' => KolomShotlist::keyBerperan(PeranKolomShotlist::DURATION, $styleId),
            'vo' => $this->kolom()->first(
                fn ($k) => Str::lower($k->key) === 'vo' || Str::contains(Str::lower($k->label), 'vo')
            )?->key,
        ];
    }

    /**
     * Susun baris menjadi pohon Scene → grup VO → shot untuk tampilan tree.
     * Aturan: baris diurutkan sesuai 'urutan'; VO hanya ditulis pada shot pertama,
     * baris berikutnya dengan VO kosong dianggap masih memakai VO yang sama
     * (mengikuti aturan penulisan shotlist studio).
     *
     * @param  Collection<int, ShotlistRow>  $rows
     * @return array<int, array{scene: string, detik: int, grup: array<int, array{vo: string, detik: int, rows: array<int, ShotlistRow>}>}>
     */
    private function pohon(Collection $rows): array
    {
        ['scene' => $sceneKey, 'dur' => $durKey, 'vo' => $voKey] = $this->kunciTree();

        $pohon = [];
        foreach ($rows as $row) {
            $data = $row->data ?? [];
            $scene = trim((string) ($sceneKey ? ($data[$sceneKey] ?? '') : '')) ?: 'Tanpa scene';
            $vo = trim((string) ($voKey ? ($data[$voKey] ?? '') : ''));
            $detik = (int) ($durKey ? ($data[$durKey] ?? 0) : 0);

            // Scene baru → node baru (scene sama tapi terpisah urutannya tetap digabung ke node terakhirnya).
            $idxScene = count($pohon) - 1;
            if ($idxScene < 0 || $pohon[$idxScene]['scene'] !== $scene) {
                $pohon[] = ['scene' => $scene, 'detik' => 0, 'grup' => []];
                $idxScene = count($pohon) - 1;
            }

            // VO terisi → grup baru; kosong → lanjutkan grup terakhir (VO yang sama).
            $idxGrup = count($pohon[$idxScene]['grup']) - 1;
            if ($vo !== '' || $idxGrup < 0) {
                $pohon[$idxScene]['grup'][] = ['vo' => $vo, 'detik' => 0, 'rows' => []];
                $idxGrup = count($pohon[$idxScene]['grup']) - 1;
            }

            $pohon[$idxScene]['grup'][$idxGrup]['rows'][] = $row;
            $pohon[$idxScene]['grup'][$idxGrup]['detik'] += $detik;
            $pohon[$idxScene]['detik'] += $detik;
        }

        return $pohon;
    }

    /**
     * Rekap tabel: total scene unik, total shot (baris), dan total durasi
     * (dari kolom berperan DURATION) dalam detik + format menit:detik.
     *
     * @param  Collection<int, ShotlistRow>  $rows
     * @return array{scene: int, shot: int, detik: int, menit: string}
     */
    private function rekap(Collection $rows): array
    {
        $styleId = $this->styleId();
        $sceneKey = KolomShotlist::keyBerperan(PeranKolomShotlist::SCENE, $styleId);
        $durKey = KolomShotlist::keyBerperan(PeranKolomShotlist::DURATION, $styleId);

        $scenes = $sceneKey
            ? $rows->map(fn ($r) => trim((string) ($r->data[$sceneKey] ?? '')))->filter()->unique()->count()
            : 0;
        $detik = $durKey
            ? (int) $rows->sum(fn ($r) => (int) ($r->data[$durKey] ?? 0))
            : 0;

        return [
            'scene' => $scenes,
            'shot' => $rows->count(),
            'detik' => $detik,
            'menit' => intdiv($detik, 60).':'.str_pad((string) ($detik % 60), 2, '0', STR_PAD_LEFT),
        ];
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

        $rows = ShotlistRow::where('project_id', $this->proyekId)->orderBy('urutan')->get();

        return view('livewire.produksi.shotlist', [
            'proyek' => Proyek::findOrFail($this->proyekId),
            'bisaKelola' => $this->bolehKelola(),
            'bisaIsiSkenario' => $this->bolehIsiSkenario(),
            'aiAktif' => app(NineRouterService::class)->aktif(),
            'kolom' => $this->kolom(),
            'rows' => $rows,
            'pohon' => $this->pohon($rows),
            'kunciTree' => $this->kunciTree(),
            'rekap' => $this->rekap($rows),
            'belumDigenerate' => ShotlistRow::where('project_id', $this->proyekId)->whereNull('shot_id')->count(),
            // Status tahap Shotlist (pra-produksi) untuk isyarat: generate idealnya setelah disetujui.
            'shotlistDisetujui' => $shotlistStatus === TaskStatus::APPROVED,
            'shotlistStatusLabel' => $shotlistStatus?->label(),
        ]);
    }
}
