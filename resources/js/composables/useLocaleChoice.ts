import { computed, type ComputedRef } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToasts } from '@/composables/useToasts';
import { currentLocale, setLocale } from '@/lib/i18n';
import { useAuthStore } from '@/stores/auth';
import type { Locale } from '@/types/models';

/**
 * Changing the interface's language, from the footer or the preferences page: applied at once and remembered on
 * this device; a verified user's choice is also saved on their account (optimistic, reverted on failure), which
 * sets the language of their emails.
 */
export function useLocaleChoice(): { locale: ComputedRef<Locale>; changeLocale: (locale: Locale) => Promise<void> } {
    const auth = useAuthStore();
    const { showToast } = useToasts();
    const { t } = useI18n();

    async function changeLocale(locale: Locale): Promise<void> {
        const previousLocale = currentLocale();
        if (locale === previousLocale) {
            return;
        }
        setLocale(locale);
        if (!auth.isVerified) {
            return;
        }
        try {
            await auth.updatePreferences({ locale });
            showToast(t('profile.preferences.saved'));
        } catch {
            setLocale(previousLocale);
            showToast(t('profile.preferences.failed'), 'error');
        }
    }

    return { locale: computed(currentLocale), changeLocale };
}
