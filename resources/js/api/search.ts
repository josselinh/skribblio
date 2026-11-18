import { http } from '@/lib/http';
import type { PaginatedResponse } from '@/types/api';
import type { AuthorFilter, GroupCollection, WordGroup } from '@/types/models';

export interface SearchParams {
    /** Searched in names only; empty lists everything visible. */
    q: string;
    author: AuthorFilter;
    page: number;
    /** 12 by default, 24 at most. */
    perPage?: number;
}

function toQuery({ q, author, page, perPage }: SearchParams) {
    return { q: q || undefined, author, page, per_page: perPage };
}

/** Groups the current user can see (own and public), names starting with `q` first. */
export async function fetchWordGroupSearchResults(params: SearchParams): Promise<PaginatedResponse<WordGroup>> {
    const { data } = await http.get<PaginatedResponse<WordGroup>>('/api/search/word-groups', { params: toQuery(params) });

    return data;
}

/** Collections the current user can see (own and public), names starting with `q` first. */
export async function fetchGroupCollectionSearchResults(
    params: SearchParams,
): Promise<PaginatedResponse<GroupCollection>> {
    const { data } = await http.get<PaginatedResponse<GroupCollection>>('/api/search/group-collections', {
        params: toQuery(params),
    });

    return data;
}
