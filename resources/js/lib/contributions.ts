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
