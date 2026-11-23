import { onUnmounted, toValue, watchEffect, type MaybeRefOrGetter } from 'vue';
import { i18n } from '@/lib/i18n';

/**
 * Sets the browser tab's title ("Name · Skribblio Tools"), also read by search engines on the public pages.
 * The app name alone is restored when the page is left.
 */
export function usePageTitle(title: MaybeRefOrGetter<string | null | undefined>): void {
    const appName = i18n.global.t('common.appName');

    watchEffect(() => {
        const pageTitle = toValue(title);
        document.title = pageTitle ? `${pageTitle} · ${appName}` : appName;
    });

    onUnmounted(() => {
        document.title = appName;
    });
}
