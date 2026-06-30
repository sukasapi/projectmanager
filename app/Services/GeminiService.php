<?php

namespace App\Services;

use App\Models\LogAi;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Klien tipis untuk Google Gemini (Generative Language API) — membantu menulis
 * deskripsi. Aktif hanya bila GEMINI_API_KEY diisi. Sinkron saat request (tanpa
 * daemon, aman cPanel). Lihat PIPELINE.md §6.
 */
class GeminiService
{
    /** Fitur AI aktif bila API key tersedia. */
    public function aktif(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * Minta Gemini menuliskan teks (deskripsi) dari sebuah instruksi.
     *
     * @throws RuntimeException bila key kosong atau respons tidak valid.
     */
    public function tulisDeskripsi(string $instruksi): string
    {
        if (! $this->aktif()) {
            throw new RuntimeException('GEMINI_API_KEY belum diatur.');
        }

        $model = config('services.gemini.model', 'gemini-2.0-flash');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        try {
            $response = Http::timeout((int) config('services.gemini.timeout', 20))
                ->withQueryParameters(['key' => config('services.gemini.key')])
                ->acceptJson()
                ->post($endpoint, [
                    'contents' => [['parts' => [['text' => $instruksi]]]],
                    'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 400],
                ]);

            $response->throw();

            $teks = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

            if ($teks === '') {
                throw new RuntimeException('Gemini tidak mengembalikan teks.');
            }

            $this->log($model, 'ok', $instruksi, $teks, null);

            return $teks;
        } catch (Throwable $e) {
            $this->log($model, 'error', $instruksi, null, $e->getMessage());

            throw $e;
        }
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
