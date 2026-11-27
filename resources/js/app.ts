import { isAxiosError } from 'axios';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import App from '@/App.vue';
import { useToasts } from '@/composables/useToasts';
import { http } from '@/lib/http';
import { i18n } from '@/lib/i18n';
import { router } from '@/router';
import { useAuthStore } from '@/stores/auth';

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(i18n);

/** How long the maintenance notification stays, and how long before another failing request may show it again. */
const MAINTENANCE_TOAST_MS = 8000;
let maintenanceToastShownAt = 0;

/**
 * Says once that the site is down for maintenance (503, `php artisan down`) when the application was already open,
 * rather than leaving only the generic errors of the requests failing at the same time. Page loads get the
 * maintenance page itself (`errors/503.blade.php`).
 */
function showMaintenanceToast(): void {
    const now = Date.now();
    if (now - maintenanceToastShownAt < MAINTENANCE_TOAST_MS) {
        return;
    }
    maintenanceToastShownAt = now;
    useToasts().showToast(i18n.global.t('common.maintenance'), 'info', MAINTENANCE_TOAST_MS);
}

/**
 * Sends the user back to the right screen when the server says the session
 * expired (401) or the email address still needs to be verified (403), and tells them when it is down for
 * maintenance (503).
 */
http.interceptors.response.use(undefined, async (error: unknown) => {
    if (isAxiosError(error) && error.response?.status === 503) {
        showMaintenanceToast();
    } else if (isAxiosError(error) && error.config?.url !== '/api/user') {
        const auth = useAuthStore();
        const status = error.response?.status;

        if (status === 401 || status === 419) {
            auth.clear();
            await router.push({ name: 'login' });
        } else if (status === 403 && auth.isAuthenticated && !auth.isVerified) {
            await router.push({ name: 'verify-email' });
        }
    }

    return Promise.reject(error);
});

app.mount('#app');
