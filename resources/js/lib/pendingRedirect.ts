/**
 * Remembers the page a logged-out or unverified user was on or wanted to open (e.g. a share link), so that they
 * land on it once logged in or their email verified, even though the verification and password reset links
 * open in a new tab. Only internal paths are kept, to avoid open redirects.
 * Storage may be unavailable (private browsing): the redirect is then simply lost.
 */
const STORAGE_KEY = 'pending-redirect';

export function rememberPendingRedirect(path: string): void {
    if (!isInternalPath(path)) {
        return;
    }
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

        return path && isInternalPath(path) ? path : null;
    } catch {
        return null;
    }
}

function isInternalPath(path: string): boolean {
    return path.startsWith('/') && !path.startsWith('//') && !path.startsWith('/\\');
}
