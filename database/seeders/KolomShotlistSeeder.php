<?php

namespace Database\Seeders;

use App\Models\KolomShotlist;
use Illuminate\Database\Seeder;

/**
 * Kolom Shotlist default (mengikuti contoh spreadsheet studio). Admin bebas
 * menambah/menghapus/urutkan/menandai peran sesuai kebutuhan studio. Idempoten.
 */
class KolomShotlistSeeder extends Seeder
{
    public function run(): void
    {
        $kolom = [
            ['key' => 'scene', 'label' => 'Scene', 'tipe' => 'text', 'peran' => 'scene'],
            ['key' => 'shot_no', 'label' => 'Shot No#', 'tipe' => 'text', 'peran' => 'shot_code'],
            ['key' => 'location', 'label' => 'Location', 'tipe' => 'text'],
            ['key' => 'dur_vo', 'label' => 'Duration VO (s)', 'tipe' => 'number'],
            ['key' => 'dur_animate', 'label' => 'Duration Animate (s)', 'tipe' => 'number', 'peran' => 'duration'],
            ['key' => 'dur_realtime', 'label' => 'Realtime Duration (s)', 'tipe' => 'number'],
            ['key' => 'size', 'label' => 'Type of Shot - Size', 'tipe' => 'select', 'opsi' => ['ELS', 'VLS', 'LS', 'MLS', 'MS', 'MCU', 'CU', 'BCU', 'ECU']],
            ['key' => 'angle', 'label' => 'Type of Shot - Angle', 'tipe' => 'select', 'opsi' => ['EL', 'HL', 'LA', 'HA', 'BEV', 'OTS', 'POV']],
            ['key' => 'move_type', 'label' => 'Movement - Type', 'tipe' => 'select', 'opsi' => ['Static', 'Pan', 'Tilt', 'Dolly', 'Track', 'Crane', 'Zoom', 'Handheld']],
            ['key' => 'move_dir', 'label' => 'Movement - Direction', 'tipe' => 'select', 'opsi' => ['Up', 'Down', 'Left', 'Right', 'In', 'Out']],
            ['key' => 'vo', 'label' => 'VO', 'tipe' => 'text'],
            ['key' => 'visual', 'label' => 'Visual', 'tipe' => 'text'],
            ['key' => 'detail_visual', 'label' => 'Detail Visual', 'tipe' => 'text'],
            ['key' => 'karakter', 'label' => 'Karakter', 'tipe' => 'text'],
        ];

        foreach ($kolom as $i => $k) {
            KolomShotlist::firstOrCreate(
                ['key' => $k['key']],
                [
                    'label' => $k['label'],
                    'tipe' => $k['tipe'],
                    'opsi' => $k['opsi'] ?? null,
                    'peran' => $k['peran'] ?? null,
                    'urutan' => $i + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
