<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Background matches the LuxBet theme in app.css to avoid a white flash --}}
        <style nonce="{{ Vite::cspNonce() }}">
            html {
                background-color: #12081f;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        {{-- Server-rendered on every page so frontend state can never hide it. --}}
        <div role="status" class="bg-demo-band px-4 py-2 text-center text-[13px] text-demo-band-foreground">
            <strong class="font-bold tracking-[0.1em]">{{ config('luxbet.demo_notice.title') }}</strong>
            <span class="hidden sm:inline"> · {{ config('luxbet.demo_notice.body') }}</span>
        </div>
        <x-inertia::app />
    </body>
</html>
