import { createI18n } from 'vue-i18n';
import en from '@/lang/en';
import fr from '@/lang/fr';
import type { FavoriteKind, Locale } from '@/types/models';

export type MessageSchema = typeof fr;

declare module 'vue-i18n' {
    export interface DefineLocaleMessage extends MessageSchema {}
}

/** Languages of the interface, in the order they are offered. */
export const LOCALES: Locale[] = ['fr', 'en'];

/** Storage key of the language last used on this device, also read by `app.blade.php` before the first paint. */
export const LOCALE_STORAGE_KEY = 'locale';

/** BCP 47 tags used by the `Intl` formatters. */
const INTL_LOCALES: Record<Locale, string> = { fr: 'fr-FR', en: 'en-US' };

function isLocale(value: unknown): value is Locale {
    return LOCALES.includes(value as Locale);
}

/**
 * The language of a visitor: the one last chosen on this device, otherwise the first French or English one of
 * the browser, otherwise English. Storage may be unavailable (private browsing).
 */
function detectLocale(): Locale {
    try {
        const stored = localStorage.getItem(LOCALE_STORAGE_KEY);
        if (isLocale(stored)) {
            return stored;
        }
    } catch {
        // Storage unavailable.
    }
    const browserLocales = navigator.languages.length > 0 ? navigator.languages : [navigator.language];

    return browserLocales.map((tag) => tag.slice(0, 2).toLowerCase()).find(isLocale) ?? 'en';
}

/**
 * French plural rule: 0 and 1 take the singular. With three choices ("aucun mot | {n} mot | {n} mots"),
 * the first one is used for 0. English keeps vue-i18n's default rule (0 takes the plural).
 */
function frenchPluralRule(choice: number, choicesLength: number): number {
    if (choicesLength === 3) {
        return Math.min(Math.abs(choice), 2);
    }

    return Math.abs(choice) > 1 ? 1 : 0;
}

/**
 * Translations of the SPA (`resources/js/lang`), in the Composition API mode. Components use `$t` in
 * templates or `useI18n()` in scripts; modules outside components use `i18n.global.t`.
 */
export const i18n = createI18n<[MessageSchema], Locale, false>({
    legacy: false,
    locale: detectLocale(),
    fallbackLocale: 'fr',
    messages: { fr, en },
    pluralRules: { fr: frenchPluralRule },
});

/** The current language of the interface. */
export function currentLocale(): Locale {
    return i18n.global.locale.value;
}

/**
 * Switches the interface to this language and remembers it on this device (the server gets it in the
 * `X-Locale` header of every request, see `@/lib/http`). A logged-in user's choice is also saved on their account.
 */
export function setLocale(locale: Locale): void {
    i18n.global.locale.value = locale;
    document.documentElement.lang = locale;
    try {
        localStorage.setItem(LOCALE_STORAGE_KEY, locale);
    } catch {
        // Storage unavailable.
    }
}

document.documentElement.lang = currentLocale();

/** BCP 47 tag of the current language, for the `Intl` formatters and `localeCompare` (reactive in templates). */
export function intlLocale(): string {
    return INTL_LOCALES[currentLocale()];
}

/** Key of the group or collection variant of a message, e.g. `deletion.title.${itemKey(kind)}`. */
export function itemKey(kind: FavoriteKind): 'wordGroup' | 'groupCollection' {
    return kind === 'word-groups' ? 'wordGroup' : 'groupCollection';
}
