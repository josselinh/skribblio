import { isAxiosError } from 'axios';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import App from '@/App.vue';
import { http } from '@/lib/http';
import { i18n } from '@/lib/i18n';
import { router } from '@/router';
import { useAuthStore } from '@/stores/auth';

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(i18n);

/**
 * Sends the user back to the right screen when the server says the session
 * expired (401) or the email address still needs to be verified (403).
 */
http.interceptors.response.use(undefined, async (error: unknown) => {
    if (isAxiosError(error) && error.config?.url !== '/api/user') {
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
