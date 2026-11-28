import { createI18n } from 'vue-i18n';
import en from '@/lang/en';
import fr from '@/lang/fr';
import zh from '@/lang/zh';
import type { FavoriteKind, InterfaceLocale } from '@/types/models';

export type MessageSchema = typeof fr;

declare module 'vue-i18n' {
    export interface DefineLocaleMessage extends MessageSchema {}
}

/** Languages of the interface, in the order they are offered (the content's are `CONTENT_LOCALES`). */
export const LOCALES: InterfaceLocale[] = ['en', 'fr', 'zh'];

/** Storage key of the language last used on this device, also read by `app.blade.php` before the first paint. */
export const LOCALE_STORAGE_KEY = 'locale';

/** BCP 47 tags used by the `Intl` formatters. */
const INTL_LOCALES: Record<InterfaceLocale, string> = { fr: 'fr-FR', en: 'en-US', zh: 'zh-CN' };

function isLocale(value: unknown): value is InterfaceLocale {
    return LOCALES.includes(value as InterfaceLocale);
}

/**
 * Query parameter asking for an interface language (`?hl=fr`): the address search engines index for each language,
 * see `App\Http\Pages\IndexedLocales`. It applies to the page opened, without being remembered on the device.
 */
const LOCALE_QUERY_PARAMETER = 'hl';

/**
 * The language of a visitor: the one asked in the address, otherwise the one last chosen on this device, otherwise
 * the first French, English or Chinese one of the browser, otherwise English. Storage may be unavailable (private
 * browsing). A logged-in user's own choice replaces it once their account is loaded.
 */
function detectLocale(): InterfaceLocale {
    const requested = new URLSearchParams(window.location.search).get(LOCALE_QUERY_PARAMETER);
    if (isLocale(requested)) {
        return requested;
    }
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
 * Chinese has no plural: a message has a single form, or two when the wording changes for one item ("删除此词语？ |
 * 删除这 {n} 个词语？"), and three, like the other languages, when it starts with the zero form.
 */
function chinesePluralRule(choice: number, choicesLength: number): number {
    if (choicesLength === 3) {
        return Math.min(Math.abs(choice), 2);
    }
    if (choicesLength === 2) {
        return Math.abs(choice) === 1 ? 0 : 1;
    }

    return 0;
}

/**
 * Translations of the SPA (`resources/js/lang`), in the Composition API mode. Components use `$t` in
 * templates or `useI18n()` in scripts; modules outside components use `i18n.global.t`.
 */
export const i18n = createI18n<[MessageSchema], InterfaceLocale, false>({
    legacy: false,
    locale: detectLocale(),
    fallbackLocale: 'en',
    messages: { fr, en, zh },
    pluralRules: { fr: frenchPluralRule, zh: chinesePluralRule },
});

/** The current language of the interface. */
export function currentLocale(): InterfaceLocale {
    return i18n.global.locale.value;
}

/**
 * Switches the interface to this language and remembers it on this device (the server gets it in the
 * `X-Locale` header of every request, see `@/lib/http`). A logged-in user's choice is also saved on their account.
 */
export function setLocale(locale: InterfaceLocale): void {
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
