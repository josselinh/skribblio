import { onUnmounted, ref, shallowRef } from 'vue';
import { START_LOCATION, type LocationQuery, type RouteLocationNormalized, type Router } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

/**
 * Breadcrumb following the path the user actually took, from the dashboard or the Explorer: "Tableau de bord ›
 * Noël › Animaux de la ferme", "Explorer › Camille Martin › Animaux de la ferme". The pages that can be a step
 * (a group, a collection, a user, and the edit pages, always last) give their label with `useBreadcrumb()`.
 *
 * The steps before the current page are kept in the history state of its entry, not in the URL: they survive
 * back/forward and reloads, and shared links stay clean. Arriving without them (a link, a new tab), the trail
 * starts at the dashboard, or at the Explorer for visitors.
 */

export type BreadcrumbRoot = 'dashboard' | 'explore';

export interface Crumb {
    /** Route of the step, with its query so that the Explorer comes back with its filters and page. */
    to: { name: string; params: Record<string, string>; query: LocationQuery };
    /** Name of the item or the user; the dashboard and the Explorer are translated when shown (`root`). */
    label: string | null;
    root?: BreadcrumbRoot;
}

const HISTORY_KEY = 'breadcrumb';
const ROOT_ROUTES: readonly string[] = ['dashboard', 'explore'];
const STEP_ROUTES: readonly string[] = ['word-groups.show', 'group-collections.show', 'users.show'];
const LAST_STEP_ROUTES: readonly string[] = ['word-groups.edit', 'group-collections.edit'];
/** The root and the last steps before the current page: four levels at most with it. */
const MAX_ANCESTORS = 3;

/** Steps before the current page; empty when it has no breadcrumb (dashboard, Explorer, account pages…). */
const ancestors = ref<Crumb[]>([]);
/** Label of the current page, given by the page itself once it is loaded. */
const pageLabel = shallowRef<(() => string | null | undefined) | null>(null);
/** Page of the item an edit page belongs to, a step even when the user arrived straight on the edit page. */
const pageParent = shallowRef<(() => Crumb | null) | null>(null);

export function currentBreadcrumbLabel(): string | null {
    return pageLabel.value?.() ?? null;
}

/** The parent step of an edit page, unless the trail already ends with it. */
function missingParent(trail: readonly Crumb[]): Crumb | null {
    const parent = pageParent.value?.() ?? null;
    const last = trail.at(-1);
    if (!parent || (last && last.to.name === parent.to.name && JSON.stringify(last.to.params) === JSON.stringify(parent.to.params))) {
        return null;
    }
    return parent;
}

/** Steps shown before the current page: the trail, then the item of an edit page when it is missing. */
export function breadcrumbSteps(): Crumb[] {
    const parent = missingParent(ancestors.value);
    return parent ? [...ancestors.value, parent] : ancestors.value;
}

/**
 * Gives the label of the current page (its name once loaded) to the breadcrumb, and for an edit page the page of
 * its item (`parent`).
 */
export function useBreadcrumb(label: () => string | null | undefined, parent?: () => Crumb | null): void {
    pageLabel.value = label;
    pageParent.value = parent ?? null;
    onUnmounted(() => {
        if (pageLabel.value === label) {
            pageLabel.value = null;
            pageParent.value = null;
        }
    });
}

/** Crumb of the page of a group or a collection, for the `parent` of its edit page. */
export function itemCrumb(name: 'word-groups.show' | 'group-collections.show', id: string, label: string): Crumb {
    return { to: { name, params: { id }, query: {} }, label };
}

function routeName(route: RouteLocationNormalized): string {
    return typeof route.name === 'string' ? route.name : '';
}

function stringParams(route: RouteLocationNormalized): Record<string, string> {
    return Object.fromEntries(Object.entries(route.params).map(([key, value]) => [key, Array.isArray(value) ? value.join('/') : value]));
}

function isSamePage(crumb: Crumb, route: RouteLocationNormalized): boolean {
    return crumb.to.name === routeName(route) && JSON.stringify(crumb.to.params) === JSON.stringify(stringParams(route));
}

function rootCrumb(root: BreadcrumbRoot, query: LocationQuery = {}): Crumb {
    return { to: { name: root, params: {}, query }, label: null, root };
}

/** Crumb of the page being left, or null when it cannot be a step (account pages, edit pages, not loaded yet). */
function crumbOf(route: RouteLocationNormalized): Crumb | null {
    const name = routeName(route);
    if (ROOT_ROUTES.includes(name)) {
        return rootCrumb(name as BreadcrumbRoot, route.query);
    }
    const label = currentBreadcrumbLabel();
    if (!STEP_ROUTES.includes(name) || !label) {
        return null;
    }
    return { to: { name, params: stringParams(route), query: route.query }, label };
}

function defaultAncestors(): Crumb[] {
    return [rootCrumb(useAuthStore().isVerified ? 'dashboard' : 'explore')];
}

function ancestorsFor(to: RouteLocationNormalized, from: RouteLocationNormalized, previous: Crumb[]): Crumb[] {
    const name = routeName(to);
    if (!STEP_ROUTES.includes(name) && !LAST_STEP_ROUTES.includes(name)) {
        return [];
    }
    /* Another page of the same item or user (sorting, pagination): the same trail. */
    if (routeName(from) === name && JSON.stringify(stringParams(from)) === JSON.stringify(stringParams(to))) {
        return previous;
    }
    const fromName = routeName(from);
    const comesFromTrail = ROOT_ROUTES.includes(fromName) || STEP_ROUTES.includes(fromName) || LAST_STEP_ROUTES.includes(fromName);
    if (from === START_LOCATION || !comesFromTrail) {
        return defaultAncestors();
    }

    /* Leaving an edit page, its item's page is the last step (added if the user arrived straight on it). */
    const leftCrumb = crumbOf(from) ?? (LAST_STEP_ROUTES.includes(fromName) ? missingParent(previous) : null);
    const candidates = ROOT_ROUTES.includes(fromName) && leftCrumb ? [leftCrumb] : leftCrumb ? [...previous, leftCrumb] : [...previous];
    if (candidates.length === 0) {
        return defaultAncestors();
    }

    /* Back to a page already in the trail (a click on a step): the trail stops before it. */
    const index = candidates.findIndex((crumb) => isSamePage(crumb, to));
    const trail = index >= 0 ? candidates.slice(0, index) : candidates;
    if (trail.length === 0) {
        return defaultAncestors();
    }

    return trail.length > MAX_ANCESTORS ? [...trail.slice(0, 1), ...trail.slice(trail.length - MAX_ANCESTORS + 1)] : trail;
}

/** Follows the navigations of the router, after they are done, to update the trail and its history state. */
export function trackBreadcrumb(router: Router): void {
    router.afterEach((to, from, failure) => {
        if (failure) {
            return;
        }
        const saved = (window.history.state as Record<string, unknown> | null)?.[HISTORY_KEY];
        /* Back, forward or reload: the entry kept its trail. */
        ancestors.value = Array.isArray(saved) ? (saved as Crumb[]) : ancestorsFor(to, from, ancestors.value);
        window.history.replaceState({ ...window.history.state, [HISTORY_KEY]: JSON.parse(JSON.stringify(ancestors.value)) }, '');
    });
}
