<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Applies the theme before the first paint, as resources/js/lib/theme.ts does afterwards. --}}
        <script>
            (() => {
                let theme = 'system';
                try { theme = localStorage.getItem('theme') ?? 'system'; } catch {}
                if (theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div id="app"></div>
    </body>
</html>
