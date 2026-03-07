<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? config('app.name') . ' - ' . $title : config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
        <div class="min-h-screen flex flex-col md:flex-row">
            <!-- Brand panel -->
            <div class="md:w-2/5 bg-slate-800 text-white flex flex-col justify-center px-8 py-12 md:py-16 lg:px-12">
                <a href="/" class="inline-flex items-center gap-2 text-2xl font-bold tracking-tight text-white no-underline">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 text-white font-semibold">T</span>
                    <span>TaskBook</span>
                </a>
                <p class="mt-6 text-slate-300 text-sm max-w-xs leading-relaxed">
                    Organise tasks and get things done. Sign in to your account to continue.
                </p>
                <div class="mt-10 flex gap-2">
                    <span class="h-1 w-8 rounded-full bg-slate-600"></span>
                    <span class="h-1 w-4 rounded-full bg-slate-600/60"></span>
                    <span class="h-1 w-4 rounded-full bg-slate-600/40"></span>
                </div>
            </div>

            <!-- Form panel -->
            <div class="flex-1 flex flex-col justify-center items-center px-6 py-12 md:py-16 bg-slate-50">
                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
