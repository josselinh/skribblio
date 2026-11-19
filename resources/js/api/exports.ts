import { http } from '@/lib/http';

/** Every word of a group, sorted by name. */
export async function fetchWordGroupExport(id: string): Promise<string[]> {
    const { data } = await http.get<{ data: string[] }>(`/api/word-groups/${id}/export`);

    return data.data;
}

export interface ExportedWordGroup {
    id: string;
    name: string;
    /** Sorted by name. */
    words: string[];
}

export interface GroupCollectionExport {
    /** Groups of the collection visible to the user, sorted by name. */
    word_groups: ExportedWordGroup[];
    /** Groups of the collection hidden from the user (never which ones). */
    hidden_word_groups_count: number;
}

/** Groups of a collection visible to the user, with their words. */
export async function fetchGroupCollectionExport(id: string): Promise<GroupCollectionExport> {
    const { data } = await http.get<{ data: GroupCollectionExport }>(`/api/group-collections/${id}/export`);

    return data.data;
}
