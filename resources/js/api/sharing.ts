import { http } from '@/lib/http';
import type { PaginatedResponse, ResourceResponse } from '@/types/api';
import type { AccessRequest, FavoriteKind, GroupCollection, ItemAccess, ShareInvitation, WordGroup } from '@/types/models';

/** Access request link of a private item, generated on first use (creator only). */
export async function fetchShareLink(kind: FavoriteKind, id: string): Promise<string> {
    const { data } = await http.get<ResourceResponse<{ url: string }>>(`/api/${kind}/${id}/share-link`);

    return data.data.url;
}

/** Replaces the access request link; the previous one stops working. */
export async function regenerateShareLink(kind: FavoriteKind, id: string): Promise<string> {
    const { data } = await http.post<ResourceResponse<{ url: string }>>(`/api/${kind}/${id}/share-link`);

    return data.data.url;
}

export async function fetchItemAccess(kind: FavoriteKind, id: string): Promise<ItemAccess> {
    const { data } = await http.get<ResourceResponse<ItemAccess>>(`/api/${kind}/${id}/access`);

    return data.data;
}

export async function revokeAccess(kind: FavoriteKind, id: string, userId: string): Promise<void> {
    await http.delete(`/api/${kind}/${id}/members/${userId}`);
}

/** The current user, a member, gives up their access to the item. */
export async function leaveItem(kind: FavoriteKind, id: string): Promise<void> {
    await http.delete(`/api/${kind}/${id}/membership`);
}

/** Pending requests on the current user's groups and collections, most recent first. */
export async function fetchReceivedAccessRequests(): Promise<AccessRequest[]> {
    const { data } = await http.get<{ data: AccessRequest[] }>('/api/access-requests/received');

    return data.data;
}

export async function decideAccessRequest(accessRequest: AccessRequest, accept: boolean): Promise<void> {
    await http.post(`/api/access-requests/${accessRequest.kind}/${accessRequest.id}/${accept ? 'accept' : 'refuse'}`);
}

export async function fetchShareInvitation(kind: FavoriteKind, token: string): Promise<ShareInvitation> {
    const { data } = await http.get<ResourceResponse<ShareInvitation>>(`/api/share/${kind}/${token}`);

    return data.data;
}

export async function requestAccess(kind: FavoriteKind, token: string): Promise<ShareInvitation> {
    const { data } = await http.post<ResourceResponse<ShareInvitation>>(`/api/share/${kind}/${token}/access-request`);

    return data.data;
}

/** Requests access to every group of the collection hidden from the current user. */
export async function requestHiddenWordGroupsAccess(groupCollectionId: string): Promise<number> {
    const { data } = await http.post<ResourceResponse<{ requested_count: number }>>(
        `/api/group-collections/${groupCollectionId}/hidden-word-groups/access-requests`,
    );

    return data.data.requested_count;
}

/** Private groups shared with the current user, and those requested from a share link. */
export async function fetchSharedWordGroups(page = 1): Promise<PaginatedResponse<WordGroup>> {
    const { data } = await http.get<PaginatedResponse<WordGroup>>('/api/shared/word-groups', { params: { page } });

    return data;
}

/** Private collections shared with the current user, and those requested from a share link. */
export async function fetchSharedGroupCollections(page = 1): Promise<PaginatedResponse<GroupCollection>> {
    const { data } = await http.get<PaginatedResponse<GroupCollection>>('/api/shared/group-collections', {
        params: { page },
    });

    return data;
}
