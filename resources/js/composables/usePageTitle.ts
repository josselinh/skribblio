import { onUnmounted, toValue, watchEffect, type MaybeRefOrGetter } from 'vue';
import { i18n } from '@/lib/i18n';

/**
 * Sets the browser tab's title ("Name · Skribblio Tools"), also read by search engines on the public pages.
 * The app name alone is restored when the page is left, unless the next page already set its own title (it can
 * be set up before the previous page is unmounted).
 */
export function usePageTitle(title: MaybeRefOrGetter<string | null | undefined>): void {
    const appName = i18n.global.t('common.appName');
    let ownTitle = appName;

    watchEffect(() => {
        const pageTitle = toValue(title);
        ownTitle = pageTitle ? `${pageTitle} · ${appName}` : appName;
        document.title = ownTitle;
    });

    onUnmounted(() => {
        if (document.title === ownTitle) {
            document.title = appName;
        }
    });
}
