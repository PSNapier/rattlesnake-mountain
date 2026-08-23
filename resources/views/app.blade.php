<!DOCTYPE html>
{{-- The site is light mode only: no theme class, no appearance cookie. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>Rattlesnake Mountain</title>

        {{-- <link rel="icon" href="/favicon.ico" sizes="any"> --}}
        {{-- <link rel="icon" href="/favicon.svg" type="image/svg+xml"> --}}
        {{-- <link rel="apple-touch-icon" href="/apple-touch-icon.png"> --}}
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐍</text></svg>">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=amaranth:400,400i,700,700i" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-cape-palliser-100 text-cape-palliser-700">
        @inertia
    </body>
</html>
