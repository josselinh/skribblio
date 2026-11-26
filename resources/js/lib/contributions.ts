import type { Visibility } from '@/types/models';

/**
 * Whether a group or collection is public and open to contributions from its members only
 * (mirrors `AcceptsContributions::restrictsContributionsToMembers()`).
 */
export function restrictsContributionsToMembers(item: {
    visibility: Visibility;
    accepts_contributions: boolean;
    contributions_members_only: boolean;
}): boolean {
    return item.accepts_contributions && item.contributions_members_only && item.visibility === 'public';
}

/**
 * Whether the creator can share the item through an invitation link: open to contributions, and private or
 * public open to its members only (mirrors `Shareable::isShareable()` and the `share` policy).
 */
export function isShareable(item: {
    visibility: Visibility;
    accepts_contributions: boolean;
    contributions_members_only: boolean;
}): boolean {
    return item.accepts_contributions && (item.visibility === 'private' || item.contributions_members_only);
}

/**
 * Why the members of an item that cannot be shared have no rights left, told under its members list: private and
 * closed (they no longer see it), public and closed (they see it like everyone, without contributing), public and
 * open to everyone (they contribute like everyone, their type of sharing aside). Null while it is shareable.
 */
export type InactiveSharingReason = 'privateClosed' | 'publicClosed' | 'openToEveryone';

export function inactiveSharingReason(item: {
    visibility: Visibility;
    accepts_contributions: boolean;
    contributions_members_only: boolean;
}): InactiveSharingReason | null {
    if (isShareable(item)) {
        return null;
    }
    if (!item.accepts_contributions) {
        return item.visibility === 'private' ? 'privateClosed' : 'publicClosed';
    }

    return 'openToEveryone';
}
