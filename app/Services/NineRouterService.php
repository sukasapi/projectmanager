<?php

namespace App\Services;

use App\Models\KolomShotlist;
use App\Models\LogAi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Klien tipis untuk 9Router (endpoint OpenAI-compatible /chat/completions) —
 * dipakai men-generate shotlist dari skenario. Karena OpenAI-compatible, base URL
 * bisa diarahkan ke layanan lain (tunnel 9Router, OpenRouter, dsb) tanpa ubah kode.
 * Aktif hanya bila NINEROUTER_API_KEY & NINEROUTER_MODEL diisi. Sinkron saat
 * request (tanpa daemon, aman cPanel). Lihat docs/2026-07-09_shotlist-ai.md.
 */
class NineRouterService
{
    /** Fitur AI shotlist aktif bila API key & model tersedia. */
    public function aktif(): bool
    {
        return filled(config('services.ninerouter.key')) && filled(config('services.ninerouter.model'));
    }

    /**
     * Pecah skenario menjadi baris-baris shotlist sesuai kolom studio yang aktif.
     *
     * @param  Collection<int, KolomShotlist>  $kolom  kolom aktif (urut)
     * @param  string|null  $instruksiTambahan  arahan tambahan dari petugas (mis. hal yang wajib dirinci di Detail Visual)
     * @return array<int, array<string, string>> baris shotlist, key = key kolom
     *
     * @throws RuntimeException bila konfigurasi kosong atau respons tidak valid.
     */
    public function generateShotlist(string $skenario, int $durasiTotalDetik, Collection $kolom, ?string $instruksiTambahan = null): array
    {
        if (! $this->aktif()) {
            throw new RuntimeException('NINEROUTER_API_KEY / NINEROUTER_MODEL belum diatur.');
        }

        $model = (string) config('services.ninerouter.model');
        $endpoint = rtrim((string) config('services.ninerouter.base_url'), '/').'/chat/completions';
        $instruksi = $this->susunInstruksi($skenario, $durasiTotalDetik, $kolom, $instruksiTambahan);

        try {
            $response = Http::timeout((int) config('services.ninerouter.timeout', 120))
                ->withToken((string) config('services.ninerouter.key'))
                ->acceptJson()
                ->post($endpoint, [
                    'model' => $model,
                    // 9Router default-nya streaming (SSE) — minta JSON tunggal eksplisit.
                    'stream' => false,
                    'temperature' => 0.4,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Kamu adalah asisten sutradara dan director of photography di studio animasi. Tugasmu memecah skenario menjadi shotlist produksi. Jawab HANYA dengan JSON valid, tanpa penjelasan dan tanpa markdown.'],
                        ['role' => 'user', 'content' => $instruksi],
                    ],
                ]);

            $response->throw();

            $konten = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            $rows = $this->parseBaris($konten, $kolom);

            if ($rows === []) {
                throw new RuntimeException('AI tidak mengembalikan baris shotlist yang valid.');
            }

            $this->log($model, 'ok', $instruksi, $konten, null);

            return $rows;
        } catch (Throwable $e) {
            $this->log($model, 'error', $instruksi, null, $e->getMessage());

            throw $e;
        }
    }

    /** @param  Collection<int, KolomShotlist>  $kolom */
    private function susunInstruksi(string $skenario, int $durasiTotalDetik, Collection $kolom, ?string $instruksiTambahan = null): string
    {
        $spek = $kolom->map(function (KolomShotlist $k) {
            $baris = "- \"{$k->key}\" ({$k->label}, tipe {$k->tipe}";
            if ($k->tipe === 'select' && filled($k->opsi)) {
                $baris .= ', pilih salah satu: '.implode(' | ', $k->opsi);
            }
            $baris .= match ($k->peran?->value) {
                'scene' => ') — PERAN scene: nama adegan, kelompokkan shot per adegan, format "Scene 01", "Scene 02", dst.',
                'shot_code' => ') — PERAN shot_code: kode shot UNIK berurutan, format "SC01_SH010", "SC01_SH020", dst.',
                'duration' => ') — PERAN duration: durasi shot dalam DETIK (angka bulat).',
                default => ')',
            };

            return $baris;
        })->implode("\n");

        $durKey = $kolom->first(fn ($k) => $k->peran?->value === 'duration')?->key;
        $targetDurasi = $durKey
            ? "Total nilai kolom \"{$durKey}\" seluruh baris harus mendekati {$durasiTotalDetik} detik."
            : "Perkiraan total durasi episode: {$durasiTotalDetik} detik.";

        $arahan = filled($instruksiTambahan)
            ? "\n\nINSTRUKSI TAMBAHAN DARI PETUGAS (wajib diikuti saat mengisi kolom terkait):\n".trim($instruksiTambahan)
            : '';

        return <<<PROMPT
        Pecah skenario berikut menjadi shotlist produksi animasi.

        Aturan:
        1. Balas HANYA objek JSON dengan bentuk {"rows": [ {...}, {...} ]} — tanpa teks lain, tanpa code fence.
        2. Setiap objek baris memakai PERSIS key kolom berikut (semua nilai berupa string; kosongkan "" bila tidak relevan):
        {$spek}
        3. {$targetDurasi}
        4. Jumlah baris menyesuaikan isi skenario dan target durasi (umumnya 3–8 detik per shot).
        5. Untuk kolom tipe select, gunakan salah satu opsi persis seperti tertulis.{$arahan}

        SKENARIO:
        {$skenario}
        PROMPT;
    }

    /**
     * Ambil array baris dari konten balasan model (toleran terhadap code fence).
     *
     * @param  Collection<int, KolomShotlist>  $kolom
     * @return array<int, array<string, string>>
     */
    private function parseBaris(string $konten, Collection $kolom): array
    {
        $bersih = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $konten) ?? $konten;
        $data = json_decode(trim($bersih), true);
        $rows = is_array($data) ? ($data['rows'] ?? (array_is_list($data) ? $data : [])) : [];

        $keys = $kolom->pluck('key')->all();
        $hasil = [];
        foreach ((array) $rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $baris = [];
            foreach ($keys as $key) {
                $nilai = $row[$key] ?? '';
                $baris[$key] = is_scalar($nilai) ? trim((string) $nilai) : '';
            }
            if (count(array_filter($baris, fn ($v) => $v !== '')) > 0) {
                $hasil[] = $baris;
            }
        }

        return $hasil;
    }

    private function log(string $model, string $status, string $prompt, ?string $response, ?string $error): void
    {
        LogAi::create([
            'user_id' => auth()->id(),
            'model' => $model,
            'status' => $status,
            'prompt' => mb_substr($prompt, 0, 2000),
            'response' => $response ? mb_substr($response, 0, 4000) : null,
            'error' => $error ? mb_substr($error, 0, 255) : null,
        ]);
    }
}
