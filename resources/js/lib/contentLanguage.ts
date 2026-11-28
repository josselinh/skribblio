import { currentLocale } from '@/lib/i18n';
import { useAuthStore } from '@/stores/auth';
import type { InterfaceLocale, Locale } from '@/types/models';

/** Languages of the content, in the order they are offered; some are not interface languages (`LOCALES`), and conversely. */
export const CONTENT_LOCALES: Locale[] = ['en', 'fr', 'de'];

/**
 * The language of the lists shown by default with each interface language (Chinese has no lists of its own).
 * Keep in sync with `InterfaceLocale::contentLocale()`, which gives it to the users registering in that language.
 */
const CONTENT_LOCALE_BY_INTERFACE_LOCALE: Record<InterfaceLocale, Locale> = { en: 'en', fr: 'fr', zh: 'fr' };

/** The content language matching an interface language. */
export function contentLocaleOf(locale: InterfaceLocale): Locale {
    return CONTENT_LOCALE_BY_INTERFACE_LOCALE[locale];
}

/**
 * Language of the lists offered by default (Explorer, home page, new groups and collections): the one the user
 * chose in their preferences, or, for a visitor, the one matching the interface's language (reactive: it follows
 * a change of language).
 */
export function preferredContentLanguage(): Locale {
    return useAuthStore().user?.content_locale ?? contentLocaleOf(currentLocale());
}
