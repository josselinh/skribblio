import { http } from '@/lib/http';

/** A word to export, with its score (upvotes minus downvotes). */
export interface ExportedWord {
    name: string;
    score: number;
}

/** Every word of a group, sorted by name. */
export async function fetchWordGroupExport(id: string): Promise<ExportedWord[]> {
    const { data } = await http.get<{ data: ExportedWord[] }>(`/api/word-groups/${id}/export`);

    return data.data;
}

export interface ExportedWordGroup {
    id: string;
    name: string;
    /** Sorted by name. */
    words: ExportedWord[];
}

export interface GroupCollectionExport {
    /** Groups of the collection visible to the user, sorted by name. */
    word_groups: ExportedWordGroup[];
}

/** Groups of a collection visible to the user, with their words. */
export async function fetchGroupCollectionExport(id: string): Promise<GroupCollectionExport> {
    const { data } = await http.get<{ data: GroupCollectionExport }>(`/api/group-collections/${id}/export`);

    return data.data;
}
