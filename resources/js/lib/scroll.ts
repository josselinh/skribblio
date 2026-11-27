import type { RouterScrollBehavior } from 'vue-router';

/** Space left above a list scrolled into view, so that its border does not touch the edge of the screen. */
const LIST_TOP_MARGIN_PX = 16;

/**
 * Scrolling of the router: another page starts at the top, also when going back or forward (the former position is
 * not restored); a change of the query only (page of a list, filters, sorting) keeps the position,
 * `scrollToList()` handling the pagination.
 */
export const scrollBehavior: RouterScrollBehavior = (to, from) => {
    if (to.path !== from.path) {
        return { top: 0 };
    }

    return false;
};

/**
 * Brings the top of a list back into view when it was scrolled past, e.g. after asking for another page of it at its
 * bottom; a list whose top is still visible does not move.
 */
export function scrollToList(list: Element): void {
    const top = list.getBoundingClientRect().top;
    if (top >= 0) {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    window.scrollTo({ top: window.scrollY + top - LIST_TOP_MARGIN_PX, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
}
