import { nextTick } from 'vue';
import { START_LOCATION, type Router } from 'vue-router';

/** Phones only, matching the `sm` breakpoint, and only when the system does not ask for reduced motion. */
const PHONE_QUERY = '(width < 40rem) and (prefers-reduced-motion: no-preference)';

/** Class of the root element keeping the header still during the transition (`app.css`). */
const STILL_HEADER_CLASS = 'page-transition-still-header';

/**
 * Animates the change of page on phones with the View Transitions API: the former page slides out to the left while
 * the new one slides in from the right (`::view-transition-*(root)` in `app.css`). Only following a link animates:
 * neither the first page, nor going back or forward (the swipe of phones already animates it), nor a change of the query
 * only (filters, sorting, page of a list). Browsers without the API change page as before.
 *
 * The header scrolls with the page: it stays still only when it is in view as the user leaves the page; otherwise
 * it slides with the rest, as it was out of view.
 */
export function trackPageTransitions(router: Router): void {
    let isHistoryNavigation = false;
    let finishUpdate: (() => void) | null = null;

    /* Fired before the router handles going back or forward. */
    window.addEventListener('popstate', () => {
        isHistoryNavigation = true;
    });

    router.beforeResolve((to, from) => {
        const animates =
            from !== START_LOCATION &&
            !isHistoryNavigation &&
            to.path !== from.path &&
            typeof document.startViewTransition === 'function' &&
            window.matchMedia(PHONE_QUERY).matches;
        isHistoryNavigation = false;
        if (!animates) {
            return;
        }

        const header = document.querySelector('.app-header');
        document.documentElement.classList.toggle(STILL_HEADER_CLASS, header !== null && header.getBoundingClientRect().bottom > 0);

        /*
         * The navigation goes on once the former page is captured; the transition then waits for the new page to be
         * rendered and scrolled to its top before capturing it.
         */
        return new Promise<void>((resumeNavigation) => {
            const transition = document.startViewTransition(
                () =>
                    new Promise<void>((updated) => {
                        finishUpdate = updated;
                        resumeNavigation();
                    }),
            );
            transition.finished.finally(() => document.documentElement.classList.remove(STILL_HEADER_CLASS));
        });
    });

    router.afterEach(async () => {
        if (!finishUpdate) {
            return;
        }
        const finish = finishUpdate;
        finishUpdate = null;
        /*
         * The router scrolls to the top after rendering the new page: wait for both. Not with requestAnimationFrame,
         * which does not run while the browser holds the rendering for the transition.
         */
        await nextTick();
        setTimeout(finish);
    });
}
