import { http } from '@/lib/http';
import type { PaginatedResponse, ResourceResponse } from '@/types/api';
import type { DuplicatedGroupCollection, GroupCollection, GroupCollectionDetail, Locale, Visibility } from '@/types/models';

export interface GroupCollectionPayload {
    name: string;
    /** Every group of the collection must be in it. */
    language: Locale;
    visibility: Visibility;
    accepts_contributions: boolean;
    contributions_members_only: boolean;
}

export interface CreateGroupCollectionPayload extends Omit<GroupCollectionPayload, 'language'> {
    /** The language of the groups when left out; they must all share it. */
    language?: Locale;
    /** At least one group. */
    word_group_ids: string[];
}

/** Collections created by the current user, most recently updated first. */
export async function fetchMyGroupCollections(page = 1): Promise<PaginatedResponse<GroupCollection>> {
    const { data } = await http.get<PaginatedResponse<GroupCollection>>('/api/group-collections', { params: { page } });

    return data;
}

export async function fetchGroupCollection(id: string): Promise<GroupCollectionDetail> {
    const { data } = await http.get<ResourceResponse<GroupCollectionDetail>>(`/api/group-collections/${id}`);

    return data.data;
}

export async function createGroupCollection(payload: CreateGroupCollectionPayload): Promise<GroupCollection> {
    const { data } = await http.post<ResourceResponse<GroupCollection>>('/api/group-collections', payload);

    return data.data;
}

export async function updateGroupCollection(id: string, payload: GroupCollectionPayload): Promise<GroupCollection> {
    const { data } = await http.patch<ResourceResponse<GroupCollection>>(`/api/group-collections/${id}`, payload);

    return data.data;
}

export async function attachWordGroup(groupCollectionId: string, wordGroupId: string): Promise<void> {
    await http.post(`/api/group-collections/${groupCollectionId}/word-groups`, { word_group_id: wordGroupId });
}

/**
 * Removes several groups from a collection at once: nothing is removed unless the user may remove every one
 * of them. Fails with a 422 when the collection would have no group left (hidden groups included).
 */
export async function detachWordGroups(groupCollectionId: string, wordGroupIds: string[]): Promise<void> {
    await http.delete(`/api/group-collections/${groupCollectionId}/word-groups`, { data: { word_group_ids: wordGroupIds } });
}

/**
 * Copies a collection, with copies of the groups the user can see (their own groups are kept as they are),
 * into a new private collection of the current user. Tells how many hidden groups were not copied.
 */
export async function duplicateGroupCollection(id: string): Promise<DuplicatedGroupCollection> {
    const { data } = await http.post<ResourceResponse<DuplicatedGroupCollection>>(`/api/group-collections/${id}/duplicate`);

    return data.data;
}

/** Deletes a collection; its groups are kept. */
export async function deleteGroupCollection(id: string): Promise<void> {
    await http.delete(`/api/group-collections/${id}`);
}
