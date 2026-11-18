import { createRouter, createWebHistory } from 'vue-router';
import { consumePendingRedirect, rememberPendingRedirect } from '@/lib/pendingRedirect';
import { useAuthStore } from '@/stores/auth';

declare module 'vue-router' {
    interface RouteMeta {
        /** Only reachable when logged out (login, register…). */
        guestOnly?: boolean;
        /** Requires a logged-in user, verified or not. */
        requiresAuth?: boolean;
        /** Requires a logged-in user with a verified email address. */
        requiresVerified?: boolean;
    }
}

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: { name: 'dashboard' },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: () => import('@/pages/DashboardPage.vue'),
            meta: { requiresVerified: true },
        },
        {
            path: '/exports/new',
            name: 'exports.create',
            component: () => import('@/pages/exports/CreateExportPage.vue'),
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
            meta: { requiresVerified: true },
        },
        {
            path: '/search',
            name: 'search',
            component: () => import('@/pages/search/SearchPage.vue'),
            meta: { requiresVerified: true },
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
            component: () => import('@/pages/ProfilePage.vue'),
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

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    await auth.ensureLoaded();

    if ((to.meta.requiresAuth || to.meta.requiresVerified) && !auth.isAuthenticated) {
        if (to.name === 'share') {
            rememberPendingRedirect(to.fullPath);
        }

        return { name: 'login', query: to.name !== 'dashboard' ? { redirect: to.fullPath } : {} };
    }

    if (to.meta.requiresVerified && !auth.isVerified) {
        if (to.name === 'share') {
            rememberPendingRedirect(to.fullPath);
        }

        return { name: 'verify-email' };
    }

    /* Back from the email verification link: resume the page the user wanted to open. */
    if (to.name === 'dashboard' && to.query.verified === '1') {
        const pendingRedirect = consumePendingRedirect();
        if (pendingRedirect) {
            return pendingRedirect;
        }
    }

    if (to.name === 'verify-email' && auth.isVerified) {
        return { name: 'dashboard' };
    }

    /* The current user's own page is their dashboard. */
    if (to.name === 'users.show' && to.params.id === auth.user?.id) {
        return { name: 'dashboard' };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});
