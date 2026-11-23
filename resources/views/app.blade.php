<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Applies the theme and the language before the first paint, as resources/js/lib/theme.ts and i18n.ts do afterwards. --}}
        <script>
            (() => {
                let theme = 'system';
                let locale = null;
                try {
                    theme = localStorage.getItem('theme') ?? 'system';
                    locale = localStorage.getItem('locale');
                } catch {}
                if (theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
                if (locale !== 'fr' && locale !== 'en') {
                    const browserLocales = navigator.languages.length > 0 ? navigator.languages : [navigator.language];
                    locale = browserLocales.map((tag) => tag.slice(0, 2).toLowerCase()).find((tag) => tag === 'fr' || tag === 'en') ?? 'en';
                }
                document.documentElement.lang = locale;
            })();
        </script>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div id="app"></div>
    </body>
</html>
