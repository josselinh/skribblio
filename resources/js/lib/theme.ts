import type { Style, Theme } from '@/types/models';

/**
 * Applies the colour theme: the `dark` class on the root element drives every `dark:` variant. The user's choice
 * is saved on their account and remembered on this device, so that `app.blade.php` applies it before the first
 * paint (same storage key); "system" follows the operating system's setting, even when it changes.
 * Storage may be unavailable (private browsing): the theme is then applied on load only.
 */
export const THEME_STORAGE_KEY = 'theme';

const darkSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

let currentTheme: Theme = readStoredTheme();

function readStoredTheme(): Theme {
    try {
        const stored = localStorage.getItem(THEME_STORAGE_KEY);

        return stored === 'light' || stored === 'dark' ? stored : 'system';
    } catch {
        return 'system';
    }
}

function render(): void {
    const isDark = currentTheme === 'dark' || (currentTheme === 'system' && darkSchemeQuery.matches);
    document.documentElement.classList.toggle('dark', isDark);
    syncThemeColor();
}

/**
 * Gives the browser (title bar of the installed web app, address bar on phones) the background colour of the header,
 * which depends on the theme and the style. The colour is read from the page's own classes, converted to RGB
 * through a canvas because the theme colours are written in oklch.
 */
function syncThemeColor(): void {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="theme-color"]');
    const context = document.createElement('canvas').getContext('2d', { willReadFrequently: true });
    if (!meta || !context) {
        return;
    }

    const probe = document.createElement('div');
    probe.className = 'hidden bg-white dark:bg-zinc-900';
    document.body.append(probe);
    context.fillStyle = getComputedStyle(probe).backgroundColor;
    probe.remove();

    context.fillRect(0, 0, 1, 1);
    const [red, green, blue] = context.getImageData(0, 0, 1, 1).data;
    meta.content = `rgb(${red} ${green} ${blue})`;
}

export function applyTheme(theme: Theme): void {
    currentTheme = theme;
    try {
        localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch {
        // Storage unavailable.
    }
    render();
}

darkSchemeQuery.addEventListener('change', render);
render();

/**
 * Applies the visual style: the `style-colorful` class on the root element switches to the "Coloré" look of
 * `app.css`. Like the theme, it is saved on the account and remembered on this device for `app.blade.php`.
 */
export const STYLE_STORAGE_KEY = 'style';

export function applyStyle(style: Style): void {
    try {
        localStorage.setItem(STYLE_STORAGE_KEY, style);
    } catch {
        // Storage unavailable.
    }
    document.documentElement.classList.toggle('style-colorful', style === 'colorful');
    syncThemeColor();
}
