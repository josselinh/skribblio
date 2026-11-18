import type { User } from '@/types/models';

/** "vous" for the current user, the creator's name otherwise. */
export function creatorLabel(creator: Pick<User, 'id' | 'name'> | undefined, currentUserId: string | undefined): string {
    if (!creator) {
        return '';
    }

    return creator.id === currentUserId ? 'vous' : creator.name;
}
