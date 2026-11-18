import axios from 'axios';

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

/** Initializes (or refreshes) the CSRF cookie before a state-changing auth request. */
export async function ensureCsrfCookie(): Promise<void> {
    await http.get('/sanctum/csrf-cookie');
}
