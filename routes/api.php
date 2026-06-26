<?php

use App\Http\Controllers\Api\ShotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — AnimTrack
|--------------------------------------------------------------------------
| Endpoint shot tracker. Penambahan/penghapusan shot otomatis memicu
| kalkulasi ulang total_duration scene (ShotObserver).
*/

Route::get('scenes/{adegan}/shots', [ShotController::class, 'index']);
Route::post('shots', [ShotController::class, 'store']);
Route::match(['put', 'patch'], 'shots/{shot}', [ShotController::class, 'update']);
Route::delete('shots/{shot}', [ShotController::class, 'destroy']);
