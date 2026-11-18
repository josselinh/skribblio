import { http } from '@/lib/http';
import type { FavoriteKind } from '@/types/models';

/** Every word of a group or of the groups of a collection visible to the user, sorted by name. */
export async function fetchExportWords(kind: FavoriteKind, id: string): Promise<string[]> {
    const { data } = await http.get<{ data: string[] }>(`/api/${kind}/${id}/export`);

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
