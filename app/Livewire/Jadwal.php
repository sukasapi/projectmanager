<?php

namespace App\Livewire;

use App\Enums\FaseProduksi;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\TugasShot;
use App\Models\TugasTahap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Timeline/Gantt ringan per episode: bar tugas berdasarkan start_date–deadline,
 * dikelompokkan per fase (Pra/Produksi/Pasca). Hanya Super Admin, Supervisor,
 * dan Team Lead episode yang boleh melihat (termasuk episode yang sudah closed).
 */
#[Layout('components.layouts.app')]
class Jadwal extends Component
{
    public ?int $proyekId = null;

    /** Boleh lihat = supervisory (Super Admin/Supervisor) atau team lead episode ini. */
    private function bolehLihat(Proyek $ep): bool
    {
        $u = auth()->user();

        return (bool) ($u && ($u->isSupervisory() || $ep->team_lead_id === $u->id));
    }

    public function pilihEpisode(int $id): void
    {
        $ep = Proyek::find($id);
        if ($ep && $this->bolehLihat($ep)) {
            $this->proyekId = $id;
        }
    }

    public function gantiEpisode(): void
    {
        $this->proyekId = null;
    }

    /** Kumpulkan tugas berjadwal (punya minimal deadline) dari Pra/Pasca + shot Produksi. */
    private function kumpulkanTugas(Proyek $ep): Collection
    {
        $out = collect();

        foreach (TugasTahap::where('project_id', $ep->id)->with('tahap')->get() as $t) {
            if (! $t->start_date && ! $t->deadline) {
                continue;
            }
            $fase = $t->tahap?->phase ?? FaseProduksi::PRA;
            $out->push([
                'label' => $t->tahap?->name ?? 'Tahap',
                'fase' => $fase,
                'start' => $t->start_date,
                'end' => $t->deadline,
                'status' => $t->status,
            ]);
        }

        $shotIds = Shot::whereIn('scene_id', $ep->adegan()->pluck('id'))->pluck('id');
        foreach (TugasShot::whereIn('shot_id', $shotIds)->with(['shot', 'tahap'])->get() as $t) {
            if (! $t->start_date && ! $t->deadline) {
                continue;
            }
            $out->push([
                'label' => trim(($t->shot?->shot_code ?? 'Shot').' · '.($t->tahap?->name ?? '')),
                'fase' => FaseProduksi::PRODUKSI,
                'start' => $t->start_date,
                'end' => $t->deadline,
                'status' => $t->status,
            ]);
        }

        return $out;
    }

    public function render()
    {
        $supervisor = Gate::allows('manage-tim');

        if ($this->proyekId) {
            $ep = Proyek::find($this->proyekId);
            if (! $ep || ! $this->bolehLihat($ep)) {
                $this->proyekId = null;
            }
        }

        $proyek = $this->proyekId ? Proyek::with('klien')->find($this->proyekId) : null;

        $grup = collect();
        $ticks = collect();
        $rangeStart = $rangeEnd = null;
        $tanpaJadwal = 0;

        if ($proyek) {
            $items = $this->kumpulkanTugas($proyek);
            $berjadwal = $items->filter(fn ($i) => $i['end'] || $i['start']);
            $tanpaJadwal = $items->count() - $berjadwal->count();

            foreach ($berjadwal as $i) {
                $s = $i['start'] ?? $i['end'];
                $e = $i['end'] ?? $i['start'];
                $rangeStart = ($rangeStart === null || $s->lt($rangeStart)) ? $s->copy() : $rangeStart;
                $rangeEnd = ($rangeEnd === null || $e->gt($rangeEnd)) ? $e->copy() : $rangeEnd;
            }

            if ($rangeStart && $rangeEnd) {
                $total = max(1, $rangeStart->diffInDays($rangeEnd) + 1);

                $bars = $berjadwal->map(function ($i) use ($rangeStart, $total) {
                    $s = $i['start'] ?? $i['end'];
                    $e = $i['end'] ?? $i['start'];
                    $offset = $rangeStart->diffInDays($s);
                    $len = max(1, $s->diffInDays($e) + 1);

                    return [
                        'label' => $i['label'],
                        'fase' => $i['fase'],
                        'status' => $i['status'],
                        'left' => round($offset / $total * 100, 2),
                        'width' => round(min($len, $total - $offset) / $total * 100, 2),
                        'mulai' => $s,
                        'selesai' => $e,
                    ];
                });

                // Urutkan fase Pra → Produksi → Pasca, lalu kelompokkan.
                $urut = [FaseProduksi::PRA->value => 0, FaseProduksi::PRODUKSI->value => 1, FaseProduksi::PASCA->value => 2];
                $grup = $bars
                    ->sortBy(fn ($b) => [$urut[$b['fase']->value] ?? 9, $b['mulai']->timestamp])
                    ->groupBy(fn ($b) => $b['fase']->label());

                // ~5 garis bantu tanggal.
                $segmen = min(5, $total);
                for ($k = 0; $k <= $segmen; $k++) {
                    $ticks->push([
                        'left' => round($k / $segmen * 100, 2),
                        'tanggal' => $rangeStart->copy()->addDays((int) round($k / $segmen * ($total - 1))),
                    ]);
                }
            }
        }

        // Pemilih episode: supervisor semua; lainnya hanya episode yang ia pimpin.
        $episodes = $this->proyekId ? collect() : Proyek::query()
            ->with('klien')
            ->when(! $supervisor, fn ($q) => $q->where('team_lead_id', auth()->id() ?? 0))
            ->orderByDesc('id')
            ->get();

        return view('livewire.jadwal', [
            'proyek' => $proyek,
            'episodes' => $episodes,
            'grup' => $grup,
            'ticks' => $ticks,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'tanpaJadwal' => $tanpaJadwal,
        ]);
    }
}
