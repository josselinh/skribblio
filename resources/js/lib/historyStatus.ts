/**
 * Status sent by the previous page through the history state (`router.push({ name, state: { status } })`),
 * read once then removed so that it does not show up again on reload.
 */
export function consumeHistoryStatus(): string | null {
    const { status, ...state } = (window.history.state ?? {}) as Record<string, unknown>;
    if (typeof status !== 'string') {
        return null;
    }
    window.history.replaceState(state, '');

    return status;
}
