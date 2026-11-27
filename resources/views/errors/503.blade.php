@php
    /*
     * Shown while the site is down for maintenance (`php artisan down`), for every page load. The maintenance
     * middleware runs before SetLocale, so the language comes from the browser. Self-contained (inline CSS, no
     * build assets), so that it shows even while a deployment replaces them.
     */
    $locale = request()->getPreferredLanguage(['fr', 'en', 'zh']) ?? 'fr';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('maintenance.title', [], $locale) }} · {{ config('app.name') }}</title>
        {{-- Same theme as the application, read from the same storage key as app.blade.php. --}}
        <script>
            (() => {
                let theme = 'system';
                try {
                    theme = localStorage.getItem('theme') ?? 'system';
                } catch {}
                if (theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
        <style>
            :root {
                color-scheme: light;
                --background: #fafafa;
                --card: #ffffff;
                --border: #e4e4e7;
                --text: #18181b;
                --muted: #71717a;
                --brand: oklch(0.5 0.08 250);
                --brand-hover: oklch(0.43 0.07 250);
            }

            :root.dark {
                color-scheme: dark;
                --background: #09090b;
                --card: #18181b;
                --border: #27272a;
                --text: #f4f4f5;
                --muted: #a1a1aa;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                background: var(--background);
                color: var(--text);
                font-family: ui-sans-serif, system-ui, sans-serif;
                border-top: 4px solid var(--brand);
            }

            main {
                width: 100%;
                max-width: 28rem;
                padding: 2rem 1.5rem;
                text-align: center;
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 0.75rem;
                box-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
            }

            .icon {
                font-size: 2.5rem;
                line-height: 1;
            }

            h1 {
                margin: 1rem 0 0.5rem;
                font-size: 1.25rem;
                font-weight: 600;
            }

            p {
                margin: 0;
                color: var(--muted);
                line-height: 1.5;
            }

            a {
                display: inline-block;
                margin-top: 1.5rem;
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                background: var(--brand);
                color: #ffffff;
                font-size: 0.875rem;
                font-weight: 500;
                text-decoration: none;
            }

            a:hover {
                background: var(--brand-hover);
            }
        </style>
    </head>
    <body>
        <main>
            <div class="icon" aria-hidden="true">🛠️</div>
            <h1>{{ __('maintenance.heading', [], $locale) }}</h1>
            <p>{{ __('maintenance.message', [], $locale) }}</p>
            <a href="{{ url()->current() }}">{{ __('maintenance.retry', [], $locale) }}</a>
        </main>
    </body>
</html>
