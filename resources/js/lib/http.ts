import axios from 'axios';
import { currentLocale } from '@/lib/i18n';

/**
 * Shared HTTP client. Authentication relies on Laravel's session cookie
 * (Sanctum SPA mode): axios automatically sends the XSRF-TOKEN cookie back
 * in the X-XSRF-TOKEN header, which satisfies Laravel's CSRF protection.
 */
export const http = axios.create({
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

/** Tells the server the interface's language, used for the messages of a visitor and when they register. */
http.interceptors.request.use((config) => {
    config.headers.set('X-Locale', currentLocale());

    return config;
});

/** Initializes (or refreshes) the CSRF cookie before a state-changing auth request. */
export async function ensureCsrfCookie(): Promise<void> {
    await http.get('/sanctum/csrf-cookie');
}
