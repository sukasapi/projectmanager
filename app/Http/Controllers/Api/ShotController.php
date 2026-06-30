<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateShot;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShotRequest;
use App\Http\Requests\UpdateShotRequest;
use App\Models\Adegan;
use App\Models\Shot;
use Illuminate\Http\JsonResponse;

class ShotController extends Controller
{
    /**
     * Daftar shot sebuah scene + total durasi terkini.
     */
    public function index(Adegan $adegan): JsonResponse
    {
        return response()->json([
            'scene' => [
                'id' => $adegan->id,
                'scene_name' => $adegan->scene_name,
                'total_duration' => $adegan->total_duration,
            ],
            'shots' => $adegan->shot()->orderByRaw('LENGTH(shot_code), shot_code')->get(),
        ]);
    }

    /**
     * Tambah shot baru (+ 4 sub-pipeline). total_duration scene otomatis ter-update.
     */
    public function store(StoreShotRequest $request, CreateShot $createShot): JsonResponse
    {
        $shot = $createShot->handle($request->validated());

        return response()->json([
            'message' => 'Shot berhasil ditambahkan.',
            'shot' => $shot->load('tugasShot'),
            'scene_total_duration' => $shot->adegan->fresh()->total_duration,
        ], 201);
    }

    /**
     * Ubah shot (durasi/kode/scene). Observer menghitung ulang durasi scene terdampak.
     */
    public function update(UpdateShotRequest $request, Shot $shot): JsonResponse
    {
        $shot->update($request->validated());

        return response()->json([
            'message' => 'Shot berhasil diperbarui.',
            'shot' => $shot->fresh(),
            'scene_total_duration' => $shot->adegan->fresh()->total_duration,
        ]);
    }

    /**
     * Hapus shot. Sub-task ikut terhapus (cascade) & durasi scene dihitung ulang.
     */
    public function destroy(Shot $shot): JsonResponse
    {
        $sceneId = $shot->scene_id;
        $shot->delete();

        return response()->json([
            'message' => 'Shot berhasil dihapus.',
            'scene_total_duration' => Adegan::whereKey($sceneId)->value('total_duration'),
        ]);
    }
}
