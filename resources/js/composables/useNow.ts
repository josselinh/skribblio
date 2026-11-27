import { readonly, ref } from 'vue';

const REFRESH_INTERVAL_MS = 60_000;

const now = ref(new Date());
let timer: number | undefined;

/**
 * The current time, refreshed every minute and shared by the whole application, so that relative dates
 * ("il y a 2 minutes") follow the time on a page left open.
 */
export function useNow() {
    if (timer === undefined) {
        timer = window.setInterval(() => {
            now.value = new Date();
        }, REFRESH_INTERVAL_MS);
    }

    return readonly(now);
}
