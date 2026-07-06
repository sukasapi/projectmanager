<?php

namespace App\Livewire;

use App\Models\Komentar as KomentarModel;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\TugasTahap;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Komentar berulir yang dapat ditanam pada komponen lain (shot / tahap).
 * Pemakaian: <livewire:komentar :subjek-type="App\Models\TugasShot::class" :subjek-id="$id" :key="..." />
 *
 * Akses: hanya artis yang ditugaskan pada subjek, pengelola/reviewer episode, atau
 * supervisi. subjekType/subjekId di-#[Locked] agar tak bisa diubah dari klien setelah mount.
 */
class Komentar extends Component
{
    /** Model subjek yang diizinkan menerima komentar. */
    private const SUBJEK_DIIZINKAN = [TugasShot::class, TugasTahap::class];

    #[Locked]
    public string $subjekType;

    #[Locked]
    public int $subjekId;

    public string $isi = '';

    public ?int $balasUntuk = null;

    public function mount(string $subjekType, int $subjekId): void
    {
        abort_unless(in_array($subjekType, self::SUBJEK_DIIZINKAN, true), 404);

        $this->subjekType = $subjekType;
        $this->subjekId = $subjekId;

        abort_unless($this->bolehAkses(), 403);
    }

    /** Boleh melihat/menulis komentar pada subjek ini. */
    private function bolehAkses(): bool
    {
        $u = auth()->user();
        if (! $u) {
            return false;
        }

        if ($u->isSupervisory()) {
            return true;
        }

        /** @var class-string $cls */
        $cls = $this->subjekType;
        $subjek = $cls::find($this->subjekId);
        if (! $subjek) {
            return false;
        }

        if ($subjek instanceof TugasShot) {
            $subjek->loadMissing(['shot.adegan', 'artists']);
            $proyekId = $subjek->shot?->adegan?->project_id;
            $ditugaskan = $subjek->artists->contains($u->id);
        } else { // TugasTahap
            $proyekId = $subjek->project_id;
            $ditugaskan = $subjek->artist_id === $u->id;
        }

        $proyek = $proyekId ? Proyek::find($proyekId) : null;

        return ($proyek && $proyek->team_lead_id === $u->id) || $ditugaskan;
    }

    public function balas(int $id): void
    {
        $this->balasUntuk = $id;
    }

    public function batalBalas(): void
    {
        $this->balasUntuk = null;
        $this->resetErrorBag();
    }

    public function kirim(): void
    {
        $this->validate(['isi' => ['required', 'string', 'min:1', 'max:2000']], attributes: ['isi' => 'komentar']);

        KomentarModel::create([
            'subjek_type' => $this->subjekType,
            'subjek_id' => $this->subjekId,
            'parent_id' => $this->balasUntuk,
            'author_id' => auth()->id(),
            'body' => trim($this->isi),
        ]);

        $this->reset(['isi', 'balasUntuk']);
    }

    public function hapus(int $id): void
    {
        $k = KomentarModel::where('subjek_type', $this->subjekType)
            ->where('subjek_id', $this->subjekId)
            ->find($id);

        if ($k && ($k->author_id === auth()->id() || auth()->user()?->isSupervisory())) {
            $k->delete(); // balasan ikut terhapus (cascade FK)
        }
    }

    public function render()
    {
        $komentar = KomentarModel::where('subjek_type', $this->subjekType)
            ->where('subjek_id', $this->subjekId)
            ->whereNull('parent_id')
            ->with(['author', 'balasan.author'])
            ->oldest()
            ->get();

        return view('livewire.komentar', ['komentar' => $komentar]);
    }
}
