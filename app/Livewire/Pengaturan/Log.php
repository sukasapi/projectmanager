<?php

namespace App\Livewire\Pengaturan;

use App\Models\LogAi;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Lihat log aplikasi (laravel.log) & log AI (Gemini) — khusus Super Admin.
 */
#[Layout('components.layouts.app')]
class Log extends Component
{
    public string $tab = 'app';

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-config'), 403);
    }

    public function tab(string $tab): void
    {
        $this->tab = in_array($tab, ['app', 'ai'], true) ? $tab : 'app';
    }

    /** Ambil ~60KB terakhir dari laravel.log. */
    private function appLog(): string
    {
        $path = storage_path('logs/laravel.log');
        if (! is_file($path)) {
            return 'Belum ada log aplikasi.';
        }

        $size = filesize($path);
        $fp = fopen($path, 'r');
        if ($size > 60000) {
            fseek($fp, $size - 60000);
        }
        $isi = fread($fp, 60000) ?: '';
        fclose($fp);

        return trim($isi) ?: 'Log kosong.';
    }

    public function render()
    {
        return view('livewire.pengaturan.log', [
            'appLog' => $this->tab === 'app' ? $this->appLog() : '',
            'aiLog' => $this->tab === 'ai'
                ? LogAi::with('pengguna')->latest()->limit(100)->get()
                : collect(),
            'tz' => config('kehadiran.timezone'),
        ]);
    }
}
