import { http } from '@/lib/http';
import type { FavoriteKind } from '@/types/models';

/** Every word of a group or of the groups of a collection visible to the user, sorted by name. */
export async function fetchExportWords(kind: FavoriteKind, id: string): Promise<string[]> {
    const { data } = await http.get<{ data: string[] }>(`/api/${kind}/${id}/export`);

    return data.data;
}
