<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AccountNav from '@/components/AccountNav.vue';
import AppLayout from '@/components/AppLayout.vue';
import { useLocaleChoice } from '@/composables/useLocaleChoice';
import { useToasts } from '@/composables/useToasts';
import { LOCALES } from '@/lib/i18n';
import { applyTheme } from '@/lib/theme';
import { useAuthStore } from '@/stores/auth';
import type { EmailNotification, Locale, Theme } from '@/types/models';

/**
 * The user's preferences, each saved as soon as it changes (optimistic, reverted on failure): the interface's
 * language and the theme, applied at once, the language of the lists they browse, and the emails they receive.
 */
const auth = useAuthStore();
const { showToast } = useToasts();
const { t } = useI18n();
const { locale, changeLocale } = useLocaleChoice();

const themes: Theme[] = ['system', 'light', 'dark'];

/** Emails grouped by subject, in the order they are listed. */
const emailSections: { title: string; notifications: EmailNotification[] }[] = [
    {
        title: 'profile.preferences.emails.accessRequests',
        notifications: ['word_group_access_requested', 'group_collection_access_requested', 'access_request_decided'],
    },
    {
        title: 'profile.preferences.emails.favorites',
        notifications: ['word_group_favorited', 'group_collection_favorited'],
    },
];

const theme = ref<Theme>(auth.user?.theme ?? 'system');
const contentLocale = ref<Locale>(auth.user?.content_locale ?? 'fr');

async function changeContentLocale(newLocale: Locale): Promise<void> {
    const previousLocale = contentLocale.value;
    contentLocale.value = newLocale;
    try {
        await auth.updatePreferences({ content_locale: newLocale });
        showToast(t('profile.preferences.saved'));
    } catch {
        contentLocale.value = previousLocale;
        showToast(t('profile.preferences.failed'), 'error');
    }
}
const emailNotifications = ref({ ...auth.user!.email_notifications });
const isSaving = ref(false);

async function changeTheme(newTheme: Theme): Promise<void> {
    const previousTheme = auth.user?.theme ?? 'system';
    theme.value = newTheme;
    applyTheme(newTheme);
    try {
        await auth.updatePreferences({ theme: newTheme });
        showToast(t('profile.preferences.saved'));
    } catch {
        theme.value = previousTheme;
        applyTheme(previousTheme);
        showToast(t('profile.preferences.failed'), 'error');
    }
}

async function toggleEmail(notification: EmailNotification, isWanted: boolean): Promise<void> {
    emailNotifications.value[notification] = isWanted;
    isSaving.value = true;
    try {
        await auth.updatePreferences({ email_notifications: { [notification]: isWanted } });
        showToast(t('profile.preferences.saved'));
    } catch {
        emailNotifications.value[notification] = !isWanted;
        showToast(t('profile.preferences.failed'), 'error');
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-lg">
            <h1 class="text-2xl font-semibold">{{ $t('profile.preferences.title') }}</h1>
            <AccountNav class="mt-4" />

            <section class="mt-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm" aria-labelledby="language-title">
                <h2 id="language-title" class="text-lg font-semibold">{{ $t('profile.preferences.language.title') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $t('profile.preferences.language.description') }}</p>
                <div class="mt-4 grid gap-2 sm:grid-cols-2" role="radiogroup" aria-labelledby="language-title">
                    <label
                        v-for="choice in LOCALES"
                        :key="choice"
                        :lang="choice"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
                    >
                        <input type="radio" name="locale" :value="choice" :checked="locale === choice" @change="changeLocale(choice)" />
                        <span class="text-sm font-medium">{{ $t(`layout.locales.${choice}`) }}</span>
                    </label>
                </div>
            </section>

            <section class="mt-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm" aria-labelledby="content-language-title">
                <h2 id="content-language-title" class="text-lg font-semibold">{{ $t('languages.preference.title') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $t('languages.preference.description') }}</p>
                <div class="mt-4 grid gap-2 sm:grid-cols-2" role="radiogroup" aria-labelledby="content-language-title">
                    <label
                        v-for="choice in LOCALES"
                        :key="choice"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
                    >
                        <input type="radio" name="content-locale" :value="choice" :checked="contentLocale === choice" @change="changeContentLocale(choice)" />
                        <span class="text-sm font-medium">{{ $t(`languages.names.${choice}`) }}</span>
                    </label>
                </div>
            </section>

            <section class="mt-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm" aria-labelledby="theme-title">
                <h2 id="theme-title" class="text-lg font-semibold">{{ $t('profile.preferences.theme.title') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $t('profile.preferences.theme.description') }}</p>
                <div class="mt-4 grid gap-2 sm:grid-cols-3" role="radiogroup" aria-labelledby="theme-title">
                    <label
                        v-for="choice in themes"
                        :key="choice"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
                    >
                        <input type="radio" name="theme" :value="choice" :checked="theme === choice" @change="changeTheme(choice)" />
                        <span class="text-sm font-medium">{{ $t(`profile.preferences.theme.${choice}`) }}</span>
                    </label>
                </div>
            </section>

            <section class="mt-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm" aria-labelledby="emails-title">
                <h2 id="emails-title" class="text-lg font-semibold">{{ $t('profile.preferences.emails.title') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $t('profile.preferences.emails.description') }}</p>
                <fieldset v-for="section in emailSections" :key="section.title" class="mt-5">
                    <legend class="text-sm font-medium">{{ $t(section.title) }}</legend>
                    <div class="mt-2 space-y-2">
                        <label v-for="notification in section.notifications" :key="notification" class="flex items-start gap-3 text-sm">
                            <input
                                type="checkbox"
                                class="mt-0.5"
                                :checked="emailNotifications[notification]"
                                :disabled="isSaving"
                                @change="toggleEmail(notification, ($event.target as HTMLInputElement).checked)"
                            />
                            <span>{{ $t(`profile.preferences.emails.${notification}`) }}</span>
                        </label>
                    </div>
                </fieldset>
            </section>
        </div>
    </AppLayout>
</template>
