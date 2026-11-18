import { http } from '@/lib/http';
import type { PaginatedResponse } from '@/types/api';
import type { FavoriteKind, GroupCollection, WordGroup } from '@/types/models';

/** Adds (`true`) or removes (`false`) a group or a collection from the current user's favorites. */
export async function setFavorite(kind: FavoriteKind, id: string, isFavorite: boolean): Promise<void> {
    const url = `/api/${kind}/${id}/favorite`;
    await (isFavorite ? http.put(url) : http.delete(url));
}

/** Favorite groups, most recently favorited first, including those that became private. */
export async function fetchFavoriteWordGroups(page = 1): Promise<PaginatedResponse<WordGroup>> {
    const { data } = await http.get<PaginatedResponse<WordGroup>>('/api/favorites/word-groups', { params: { page } });

    return data;
}

/** Favorite collections, most recently favorited first, including those that became private. */
export async function fetchFavoriteGroupCollections(page = 1): Promise<PaginatedResponse<GroupCollection>> {
    const { data } = await http.get<PaginatedResponse<GroupCollection>>('/api/favorites/group-collections', {
        params: { page },
    });

    return data;
}
