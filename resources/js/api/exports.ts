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
}

/** Groups of a collection visible to the user, with their words. */
export async function fetchGroupCollectionExport(id: string): Promise<GroupCollectionExport> {
    const { data } = await http.get<{ data: GroupCollectionExport }>(`/api/group-collections/${id}/export`);

    return data.data;
}

export interface ExportComposition {
    words: string[];
    /** Selected groups plus the groups of the selected collections visible to the user, sorted by name. */
    word_group_ids: string[];
    /** Groups of the selected collections hidden from the user, left out of the export. */
    hidden_word_groups_count: number;
}

/** Words of a selection of groups and collections, without saving anything. */
export async function fetchExportComposition(wordGroupIds: string[], groupCollectionIds: string[]): Promise<ExportComposition> {
    const { data } = await http.get<{ data: ExportComposition }>('/api/exports/composition', {
        params: { word_group_ids: wordGroupIds, group_collection_ids: groupCollectionIds },
    });

    return data.data;
}
