/**
 * Remembers, on this device, the collection the user last added a group to, so that the other group pages offer to add
 * theirs to it in one click. It is stored with the user's id, so that another account on the same device does not
 * inherit it. Storage may be unavailable (private browsing): nothing is remembered then.
 */
const STORAGE_KEY = 'target-group-collection';

export function rememberTargetCollection(userId: string, groupCollectionId: string): void {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ userId, groupCollectionId }));
    } catch {
        // Storage unavailable.
    }
}

/** The id of the collection remembered for the user, if any. */
export function rememberedTargetCollection(userId: string): string | null {
    try {
        const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null') as unknown;
        if (
            typeof stored === 'object' &&
            stored !== null &&
            'userId' in stored &&
            'groupCollectionId' in stored &&
            stored.userId === userId &&
            typeof stored.groupCollectionId === 'string'
        ) {
            return stored.groupCollectionId;
        }
    } catch {
        // Storage unavailable or invalid value.
    }

    return null;
}
