import { http } from '@/lib/http';
import type { PaginatedResponse, ResourceResponse } from '@/types/api';
import type { Visibility, WordGroup } from '@/types/models';

export interface WordGroupPayload {
    name: string;
    visibility: Visibility;
    accepts_contributions: boolean;
    contributions_members_only: boolean;
}

/** Groups created by the current user, most recently updated first. */
export async function fetchMyWordGroups(page = 1): Promise<PaginatedResponse<WordGroup>> {
    const { data } = await http.get<PaginatedResponse<WordGroup>>('/api/word-groups', { params: { page } });

    return data;
}

/** Groups the current user can see (their own first, then public ones) whose name contains the query. */
export async function searchWordGroups(query: string): Promise<WordGroup[]> {
    const { data } = await http.get<{ data: WordGroup[] }>('/api/word-groups/search', { params: { q: query } });

    return data.data;
}

export async function fetchWordGroup(id: string): Promise<WordGroup> {
    const { data } = await http.get<ResourceResponse<WordGroup>>(`/api/word-groups/${id}`);

    return data.data;
}

export async function createWordGroup(payload: WordGroupPayload): Promise<WordGroup> {
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
