import { currentLocale } from '@/lib/i18n';
import { useAuthStore } from '@/stores/auth';
import type { Locale } from '@/types/models';

/**
 * Language of the lists offered by default (Explorer, new groups and collections): the one the user chose in
 * their preferences, or, for a visitor, the interface's.
 */
export function preferredContentLanguage(): Locale {
    return useAuthStore().user?.content_locale ?? currentLocale();
}
