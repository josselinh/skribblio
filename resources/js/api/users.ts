import { http } from '@/lib/http';
import type { PaginatedResponse, ResourceResponse } from '@/types/api';
import type { GroupCollection, User, WordGroup } from '@/types/models';

/** Another user's public identity (no email). */
export async function fetchUser(id: string): Promise<Pick<User, 'id' | 'name'>> {
    const { data } = await http.get<ResourceResponse<Pick<User, 'id' | 'name'>>>(`/api/users/${id}`);

    return data.data;
}

/** The user's groups the current user can see (public and shared), most recently updated first. */
export async function fetchUserWordGroups(id: string, page = 1): Promise<PaginatedResponse<WordGroup>> {
    const { data } = await http.get<PaginatedResponse<WordGroup>>(`/api/users/${id}/word-groups`, { params: { page } });

    return data;
}

/** The user's collections the current user can see (public and shared), most recently updated first. */
export async function fetchUserGroupCollections(id: string, page = 1): Promise<PaginatedResponse<GroupCollection>> {
    const { data } = await http.get<PaginatedResponse<GroupCollection>>(`/api/users/${id}/group-collections`, {
        params: { page },
    });

    return data;
}
