import { http } from '@/lib/http';
import type { PaginatedResponse, ResourceResponse } from '@/types/api';
import type { Visibility, WordGroup } from '@/types/models';

export interface WordGroupPayload {
    name: string;
    visibility: Visibility;
}

/** Groups created by the current user, most recently updated first. */
export async function fetchMyWordGroups(page = 1): Promise<PaginatedResponse<WordGroup>> {
    const { data } = await http.get<PaginatedResponse<WordGroup>>('/api/word-groups', { params: { page } });

    return data;
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
