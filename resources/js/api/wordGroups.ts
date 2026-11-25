import { http } from '@/lib/http';
import type { CollectionTargetsResponse, PaginatedResponse, ResourceResponse } from '@/types/api';
import type { Locale, Visibility, WordGroup } from '@/types/models';

export interface WordGroupPayload {
    name: string;
    visibility: Visibility;
    accepts_contributions: boolean;
    contributions_members_only: boolean;
}

export interface CreateWordGroupPayload extends WordGroupPayload {
    /** Chosen on creation, it cannot be changed afterwards. */
    language: Locale;
}

/** Groups created by the current user, most recently updated first. */
export async function fetchMyWordGroups(page = 1): Promise<PaginatedResponse<WordGroup>> {
    const { data } = await http.get<PaginatedResponse<WordGroup>>('/api/word-groups', { params: { page } });

    return data;
}

/**
 * Groups the current user can see (their own first, then public ones) whose name contains the query, in
 * `language` only when given.
 */
export async function searchWordGroups(query: string, language?: Locale): Promise<WordGroup[]> {
    const { data } = await http.get<{ data: WordGroup[] }>('/api/word-groups/search', { params: { q: query, language } });

    return data.data;
}

/**
 * Collections the current user can add the group to, in its language, each flagged when it already contains it: those
 * whose name contains `query`, only `groupCollectionId` when given, or suggestions (favorites, otherwise own ones).
 */
export async function fetchCollectionTargets(
    wordGroupId: string,
    options: { query?: string; groupCollectionId?: string } = {},
): Promise<CollectionTargetsResponse> {
    const { data } = await http.get<CollectionTargetsResponse>(`/api/word-groups/${wordGroupId}/collection-targets`, {
        params: { q: options.query || undefined, group_collection_id: options.groupCollectionId },
    });

    return data;
}

export async function fetchWordGroup(id: string): Promise<WordGroup> {
    const { data } = await http.get<ResourceResponse<WordGroup>>(`/api/word-groups/${id}`);

    return data.data;
}

export async function createWordGroup(payload: CreateWordGroupPayload): Promise<WordGroup> {
    const { data } = await http.post<ResourceResponse<WordGroup>>('/api/word-groups', payload);

    return data.data;
}

export async function updateWordGroup(id: string, payload: WordGroupPayload): Promise<WordGroup> {
    const { data } = await http.patch<ResourceResponse<WordGroup>>(`/api/word-groups/${id}`, payload);

    return data.data;
}

/** Copies a group into a new private group of the current user, closed to contributions. */
export async function duplicateWordGroup(id: string): Promise<WordGroup> {
    const { data } = await http.post<ResourceResponse<WordGroup>>(`/api/word-groups/${id}/duplicate`);

    return data.data;
}

/** Deletes a group with its words; collections containing it lose it. */
export async function deleteWordGroup(id: string): Promise<void> {
    await http.delete(`/api/word-groups/${id}`);
}
