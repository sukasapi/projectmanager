<?php

namespace App\Livewire;

use App\Models\Adegan;
use App\Models\Aset;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Pencarian global lintas episode/scene/shot/aset/artis, menghormati hak akses:
 * supervisor melihat semua + artis; lainnya hanya episode yang ditugaskan padanya.
 */
#[Layout('components.layouts.app')]
class Pencarian extends Component
{
    #[Url(as: 'q')]
    public string $q = '';

    public function render()
    {
        $supervisor = Gate::allows('manage-tim');
        $term = trim($this->q);

        $hasil = [
            'episode' => collect(),
            'scene' => collect(),
            'shot' => collect(),
            'aset' => collect(),
            'artis' => collect(),
        ];

        if (mb_strlen($term) >= 2) {
            $like = '%'.$term.'%';
            $epQuery = Proyek::query()->when(! $supervisor, fn ($x) => $x->untukUser(auth()->id() ?? 0));
            $epIds = (clone $epQuery)->pluck('id');
            $sceneIds = Adegan::whereIn('project_id', $epIds)->pluck('id');

            $hasil['episode'] = (clone $epQuery)->where('name', 'like', $like)->orderByDesc('id')->limit(10)->get();
            $hasil['scene'] = Adegan::whereIn('project_id', $epIds)->where('scene_name', 'like', $like)->with('proyek')->limit(10)->get();
            $hasil['shot'] = Shot::whereIn('scene_id', $sceneIds)->where('shot_code', 'like', $like)->with('adegan.proyek')->limit(15)->get();
            $hasil['aset'] = Aset::whereIn('project_id', $epIds)->where('name', 'like', $like)->with('proyek')->limit(10)->get();

            if ($supervisor) {
                $hasil['artis'] = User::where(fn ($x) => $x->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orderBy('name')->limit(10)->get();
            }
        }

        $total = collect($hasil)->sum(fn ($c) => $c->count());

        return view('livewire.pencarian', ['hasil' => $hasil, 'term' => $term, 'total' => $total]);
    }
}
