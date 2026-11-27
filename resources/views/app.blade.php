<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Applies the theme, the style and the language before the first paint, as resources/js/lib/theme.ts and i18n.ts do afterwards. --}}
        <script>
            (() => {
                let theme = 'system';
                let style = 'sober';
                let locale = null;
                try {
                    theme = localStorage.getItem('theme') ?? 'system';
                    style = localStorage.getItem('style') ?? 'sober';
                    locale = localStorage.getItem('locale');
                } catch {}
                if (style === 'colorful') {
                    document.documentElement.classList.add('style-colorful');
                }
                if (theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
                const locales = ['fr', 'en', 'zh'];
                if (!locales.includes(locale)) {
                    const browserLocales = navigator.languages.length > 0 ? navigator.languages : [navigator.language];
                    locale = browserLocales.map((tag) => tag.slice(0, 2).toLowerCase()).find((tag) => locales.includes(tag)) ?? 'en';
                }
                document.documentElement.lang = locale;
            })();
        </script>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        {{-- Same bar as resources/js/components/NavigationProgress.vue, until the application replaces it. --}}
        <div id="app">
            <div aria-hidden="true" class="pointer-events-none fixed inset-x-0 top-0 z-50 h-1 overflow-hidden">
                <div class="h-full w-1/3 animate-navigation-progress bg-brand-300 dark:bg-brand-400 motion-reduce:w-full motion-reduce:animate-none"></div>
            </div>
        </div>
    </body>
</html>
