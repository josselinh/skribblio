<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Read by search engines and link previews before the SPA runs: see App\Http\Controllers\PageController. --}}
        <title>{{ $meta->documentTitle() }}</title>
        <meta name="description" content="{{ $meta->descriptionText() }}">
        @if ($meta->canonicalUrl() !== null)
            <link rel="canonical" href="{{ $meta->canonicalUrl() }}">
        @endif
        @foreach ($meta->alternateUrls() as $hreflang => $alternateUrl)
            <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $alternateUrl }}">
        @endforeach
        @unless ($meta->indexable)
            <meta name="robots" content="noindex">
        @endunless
        <meta property="og:site_name" content="{{ $meta->siteName() }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $meta->title ?? $meta->siteName() }}">
        <meta property="og:description" content="{{ $meta->descriptionText() }}">
        @if ($meta->canonicalUrl() !== null)
            <meta property="og:url" content="{{ $meta->canonicalUrl() }}">
        @endif
        <meta property="og:image" content="{{ url('/icons/icon-512.png') }}">
        <meta name="twitter:card" content="summary">

        {{-- Installable as a web app; the theme colour follows the header, kept in sync by resources/js/lib/theme.ts. --}}
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" href="/favicon.ico" sizes="48x48">
        <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <meta name="apple-mobile-web-app-title" content="Skribblio">
        <meta name="theme-color" content="#ffffff">

        {{-- Applies the theme, the style and the language (the one asked with ?hl first) before the first paint, as resources/js/lib/theme.ts and i18n.ts do afterwards. --}}
        <script>
            (() => {
                let theme = 'system';
                let style = 'colorful';
                let locale = null;
                try {
                    theme = localStorage.getItem('theme') ?? 'system';
                    style = localStorage.getItem('style') ?? 'colorful';
                    locale = localStorage.getItem('locale');
                } catch {}
                if (style === 'colorful') {
                    document.documentElement.classList.add('style-colorful');
                }
                if (theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
                const locales = ['fr', 'en', 'zh'];
                const requestedLocale = new URLSearchParams(location.search).get('hl');
                if (locales.includes(requestedLocale)) {
                    locale = requestedLocale;
                }
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
