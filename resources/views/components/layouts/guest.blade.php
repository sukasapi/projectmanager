<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} · AnimTrack</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#16313f">
    <link rel="icon" href="/favicon2.ico?v=2" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon2.ico?v=2" type="image/x-icon">
    <link rel="apple-touch-icon" href="/icon.svg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    {{ $slot }}
    @livewireScripts
</body>
</html>
