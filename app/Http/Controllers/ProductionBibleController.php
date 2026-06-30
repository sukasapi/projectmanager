<?php

namespace App\Http\Controllers;

use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use App\Models\Perusahaan;
use App\Models\Proyek;
use App\Models\Tahap;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ekspor "Production Bible" sebuah episode sebagai PDF (dompdf — PHP murni, aman cPanel).
 * Berisi seluruh data: profil studio, pra-produksi, aset, breakdown shot per tahap, pasca.
 * Lihat PIPELINE.md §5.
 */
class ProductionBibleController extends Controller
{
    public function __invoke(Proyek $proyek): Response
    {
        // Akses: supervisor atau anggota yang ditugaskan di episode ini.
        abort_unless(
            Gate::allows('manage-tim') || Proyek::whereKey($proyek->id)->untukUser(auth()->id() ?? 0)->exists(),
            403
        );

        $proyek->load([
            'klien',
            'adegan' => fn ($q) => $q->orderByRaw('LENGTH(scene_name), scene_name'),
            'adegan.shot' => fn ($q) => $q->orderByRaw('LENGTH(shot_code), shot_code'),
            'adegan.shot.tugasShot.tahap',
            'adegan.shot.tugasShot.artists',
            'aset.artist',
            'tugasTahap.tahap',
            'tugasTahap.artis',
        ]);

        $tugasTahap = $proyek->tugasTahap->sortBy(fn ($t) => $t->tahap?->urutan);

        $pdf = Pdf::loadView('pdf.production-bible', [
            'perusahaan' => Perusahaan::current(),
            'proyek' => $proyek,
            'pra' => $tugasTahap->filter(fn ($t) => $t->tahap?->phase === FaseProduksi::PRA)->values(),
            'pasca' => $tugasTahap->filter(fn ($t) => $t->tahap?->phase === FaseProduksi::PASCA)->values(),
            'tahapShot' => Tahap::milikEpisode($proyek->id)->aktif()->fase(FaseProduksi::PRODUKSI)->where('level', LevelTahap::SHOT->value)->urut()->get(),
            'dicetak' => now()->timezone(config('kehadiran.timezone'))->translatedFormat('d F Y H:i'),
        ])->setPaper('a4');

        return $pdf->download('production-bible-'.Str::slug($proyek->name).'.pdf');
    }
}
