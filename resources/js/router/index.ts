import { createRouter, createWebHistory } from 'vue-router';
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
        return { name: 'login', query: to.name !== 'dashboard' ? { redirect: to.fullPath } : {} };
    }

    if (to.meta.requiresVerified && !auth.isVerified) {
        return { name: 'verify-email' };
    }

    if (to.name === 'verify-email' && auth.isVerified) {
        return { name: 'dashboard' };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});
