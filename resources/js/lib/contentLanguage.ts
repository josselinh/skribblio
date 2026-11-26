import { currentLocale } from '@/lib/i18n';
import { useAuthStore } from '@/stores/auth';
import type { InterfaceLocale, Locale } from '@/types/models';

/** Languages of the content, in the order they are offered: fewer than the interface's (`LOCALES`). */
export const CONTENT_LOCALES: Locale[] = ['fr', 'en'];

/** The content language matching an interface language, English when the content is not offered in it. */
export function contentLocaleOf(locale: InterfaceLocale): Locale {
    return CONTENT_LOCALES.includes(locale as Locale) ? (locale as Locale) : 'en';
}

/**
 * Language of the lists offered by default (Explorer, new groups and collections): the one the user chose in
 * their preferences, or, for a visitor, the interface's (English when the content is not offered in it).
 */
export function preferredContentLanguage(): Locale {
    return useAuthStore().user?.content_locale ?? contentLocaleOf(currentLocale());
}
