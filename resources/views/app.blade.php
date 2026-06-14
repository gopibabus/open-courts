<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Open Courts') }}</title>

        <link rel="icon" type="image/x-icon" href="/{{ config('branding.favicon') }}" sizes="any">
        <link rel="apple-touch-icon" href="/{{ config('branding.logo_dark') }}">

        {{-- JetBrains Mono = system typeface; Doto = dot-matrix display accents (Nothing-style). --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600,700&family=doto:400,500,700,900" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
