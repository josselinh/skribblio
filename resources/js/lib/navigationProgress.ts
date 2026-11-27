import { readonly, ref } from 'vue';
import { START_LOCATION, type RouteLocationNormalized, type Router } from 'vue-router';

/** Navigations shorter than this show nothing, so that the bar does not flash on every click. */
const DELAY_MS = 150;

const isNavigating = ref(false);

/** Whether a navigation of the router has been pending for a while, shown by `NavigationProgress`. */
export const isNavigationPending = readonly(isNavigating);

/**
 * Follows the navigations of the router: while one waits for its guards (the logged-in user) and the code of its
 * page, `isNavigationPending` becomes true. Register it before the other guards, so that their wait counts too.
 * Only the latest navigation ends it: one cancelled by a newer click keeps the bar until the newer one is done.
 */
export function trackNavigationProgress(router: Router): void {
    let timer: ReturnType<typeof setTimeout> | undefined;
    let pendingTarget: RouteLocationNormalized | null = null;

    const stop = (): void => {
        clearTimeout(timer);
        pendingTarget = null;
        isNavigating.value = false;
    };

    router.beforeEach((to, from) => {
        pendingTarget = to;
        clearTimeout(timer);
        /* The first page shows it at once, taking over the placeholder of app.blade.php. */
        if (from === START_LOCATION) {
            isNavigating.value = true;
        } else {
            timer = setTimeout(() => {
                isNavigating.value = true;
            }, DELAY_MS);
        }
    });

    router.afterEach((to) => {
        if (to === pendingTarget) {
            stop();
        }
    });

    router.onError(stop);
}
