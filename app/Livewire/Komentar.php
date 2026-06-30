<?php

namespace App\Livewire;

use App\Models\Komentar as KomentarModel;
use Livewire\Component;

/**
 * Komentar berulir yang dapat ditanam pada komponen lain (shot / tahap).
 * Pemakaian: <livewire:komentar :subjek-type="App\Models\TugasShot::class" :subjek-id="$id" :key="..." />
 */
class Komentar extends Component
{
    public string $subjekType;

    public int $subjekId;

    public string $isi = '';

    public ?int $balasUntuk = null;

    public function mount(string $subjekType, int $subjekId): void
    {
        $this->subjekType = $subjekType;
        $this->subjekId = $subjekId;
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
