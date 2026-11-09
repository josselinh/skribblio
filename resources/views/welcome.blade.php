<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Skribbl.io - tools</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-600 min-h-screen flex flex-col justify-between">
    <div class="relative min-h-screen flex flex-col justify-center items-center">
        <!-- Top Right Links -->
        <div class="absolute top-4 right-6 space-x-4 text-xs font-semibold uppercase tracking-wider text-gray-500">
            @auth
                <a href="{{ route('sentence.index') }}" class="hover:text-gray-900 transition">{{ __('menu.home') }}</a>
            @else
                <a href="{{ route('login') }}" class="hover:text-gray-900 transition">{{ __('menu.login') }}</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="hover:text-gray-900 transition">{{ __('menu.register') }}</a>
                @endif
            @endauth
        </div>

        <!-- Center Content -->
        <div class="text-center px-4">
            <h1 class="text-6xl sm:text-7xl md:text-8xl font-light text-gray-700 tracking-tight mb-8">
                Skribbl.io <sup class="text-2xl sm:text-3xl text-indigo-600 font-normal">tools</sup>
            </h1>

            <div class="flex flex-wrap justify-center gap-6 text-xs sm:text-sm font-semibold uppercase tracking-widest text-gray-500">
                @auth
                    <a href="{{ route('sentence.index') }}" class="hover:text-gray-900 transition">{{ __('menu.sentences.root') }}</a>
                    <a href="{{ route('group.index') }}" class="hover:text-gray-900 transition">{{ __('menu.groups.root') }}</a>
                @endauth
                <a href="{{ route('sentence.export') }}" class="hover:text-gray-900 transition">{{ __('menu.sentences.export') }}</a>
                <a href="{{ route('release') }}" class="hover:text-gray-900 transition">v1.0.4</a>
            </div>
        </div>
    </div>
</body>
</html>
