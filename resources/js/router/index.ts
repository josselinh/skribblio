import { createRouter, createWebHistory } from 'vue-router';
import { useToasts } from '@/composables/useToasts';
import { trackBreadcrumb } from '@/lib/breadcrumb';
import { i18n } from '@/lib/i18n';
import { trackNavigationProgress } from '@/lib/navigationProgress';
import { trackPageTransitions } from '@/lib/pageTransition';
import { consumePendingRedirect, rememberPendingRedirect } from '@/lib/pendingRedirect';
import { scrollBehavior } from '@/lib/scroll';
import { useAuthStore } from '@/stores/auth';

declare module 'vue-router' {
    interface RouteMeta {
        /** Only reachable when logged out (login, register…). */
        guestOnly?: boolean;
        /** Requires a logged-in user, verified or not. */
        requiresAuth?: boolean;
        /** Requires a logged-in user with a verified email address. */
        requiresVerified?: boolean;
        /**
         * Open to visitors (public groups and collections, Explorer): actions that need an account are hidden
         * or replaced by an invitation to log in. The other app pages require a verified user.
         */
        isPublic?: boolean;
    }
}

export const router = createRouter({
    history: createWebHistory(),
    scrollBehavior,
    routes: [
        {
            /* The home page, for visitors and logged-in users alike. */
            path: '/',
            name: 'home',
            component: () => import('@/pages/HomePage.vue'),
            meta: { isPublic: true },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: () => import('@/pages/DashboardPage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/groups/new',
            name: 'word-groups.create',
            component: () => import('@/pages/word-groups/CreateWordGroupPage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/groups/:id',
            name: 'word-groups.show',
            component: () => import('@/pages/word-groups/ShowWordGroupPage.vue'),
            meta: { isPublic: true },
        },
        {
            path: '/groups/:id/edit',
            name: 'word-groups.edit',
            component: () => import('@/pages/word-groups/EditWordGroupPage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/collections/new',
            name: 'group-collections.create',
            component: () => import('@/pages/group-collections/CreateGroupCollectionPage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/collections/:id',
            name: 'group-collections.show',
            component: () => import('@/pages/group-collections/ShowGroupCollectionPage.vue'),
            meta: { isPublic: true },
        },
        {
            path: '/collections/:id/edit',
            name: 'group-collections.edit',
            component: () => import('@/pages/group-collections/EditGroupCollectionPage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/explore',
            name: 'explore',
            component: () => import('@/pages/explore/ExplorePage.vue'),
            meta: { isPublic: true },
        },
        {
            path: '/share/:type(groups|collections)/:token',
            name: 'share',
            component: () => import('@/pages/share/SharePage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/profile',
            name: 'profile',
            component: () => import('@/pages/account/ProfilePage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/security',
            name: 'security',
            component: () => import('@/pages/account/SecurityPage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/preferences',
            name: 'preferences',
            component: () => import('@/pages/account/PreferencesPage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/users/:id',
            name: 'users.show',
            component: () => import('@/pages/users/UserPage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/pages/auth/LoginPage.vue'),
            meta: { guestOnly: true },
        },
        {
            path: '/register',
            name: 'register',
            component: () => import('@/pages/auth/RegisterPage.vue'),
            meta: { guestOnly: true },
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: () => import('@/pages/auth/ForgotPasswordPage.vue'),
            meta: { guestOnly: true },
        },
        {
            path: '/reset-password/:token',
            name: 'reset-password',
            component: () => import('@/pages/auth/ResetPasswordPage.vue'),
            meta: { guestOnly: true },
        },
        {
            path: '/email/verify',
            name: 'verify-email',
            component: () => import('@/pages/auth/VerifyEmailPage.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: () => import('@/pages/NotFoundPage.vue'),
        },
    ],
});

trackNavigationProgress(router);
trackBreadcrumb(router);
trackPageTransitions(router);

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    await auth.ensureLoaded();

    if ((to.meta.requiresAuth || to.meta.requiresVerified) && !auth.isAuthenticated) {
        if (to.name === 'share') {
            rememberPendingRedirect(to.fullPath);
        }

        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.requiresVerified && !auth.isVerified) {
        if (to.name === 'share') {
            rememberPendingRedirect(to.fullPath);
        }

        return { name: 'verify-email' };
    }

    /*
     * Back from the email verification link (Fortify sends it to the dashboard): welcomes the user, then resumes the
     * page they were on or wanted to open, otherwise the home page.
     */
    if (to.name === 'dashboard' && to.query.verified === '1') {
        useToasts().showToast(i18n.global.t('dashboard.emailVerified'));

        return consumePendingRedirect() ?? { name: 'home' };
    }

    if (to.name === 'verify-email' && auth.isVerified) {
        return { name: 'dashboard' };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});
