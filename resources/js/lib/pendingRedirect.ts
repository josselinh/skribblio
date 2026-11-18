/**
 * Remembers the page a logged-out or unverified user wanted to open (e.g. a share link), so that they
 * land on it once their email is verified, even though the verification link opens in a new tab.
 * Storage may be unavailable (private browsing): the redirect is then simply lost.
 */
const STORAGE_KEY = 'pending-redirect';

export function rememberPendingRedirect(path: string): void {
    try {
        localStorage.setItem(STORAGE_KEY, path);
    } catch {
        // Storage unavailable.
    }
}

/** Returns the remembered path, if any, and forgets it. */
export function consumePendingRedirect(): string | null {
    try {
        const path = localStorage.getItem(STORAGE_KEY);
        localStorage.removeItem(STORAGE_KEY);

        return path && path.startsWith('/') && !path.startsWith('//') ? path : null;
    } catch {
        return null;
    }
}
