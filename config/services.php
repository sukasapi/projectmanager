<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     | Google Gemini — bantuan AI untuk menulis deskripsi (PIPELINE.md §6).
     | Isi GEMINI_API_KEY di .env untuk mengaktifkan; bila kosong, fitur AI tersembunyi.
     | Aman cPanel (HTTPS biasa saat request, tanpa daemon).
     */
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'timeout' => env('GEMINI_TIMEOUT', 20),
    ],

    /*
     | 9Router — endpoint OpenAI-compatible untuk generate shotlist dari skenario
     | (docs/2026-07-09_shotlist-ai.md). Base URL diarahkan ke tunnel 9Router
     | (mis. https://xxx.trycloudflare.com/v1) atau layanan OpenAI-compatible lain.
     | Isi NINEROUTER_API_KEY + NINEROUTER_MODEL untuk mengaktifkan; bila kosong,
     | tombol AI tersembunyi. Aman cPanel (HTTP keluar biasa saat request, tanpa daemon).
     */
    'ninerouter' => [
        'base_url' => env('NINEROUTER_BASE_URL', 'http://localhost:20128/v1'),
        'key' => env('NINEROUTER_API_KEY'),
        'model' => env('NINEROUTER_MODEL'),
        'timeout' => env('NINEROUTER_TIMEOUT', 120),
    ],

];
