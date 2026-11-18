import { http } from '@/lib/http';
import type { PaginatedResponse, ResourceResponse } from '@/types/api';
import type { Word } from '@/types/models';

export interface WordPayload {
    name: string;
}

/** Words of a group, most recently added first. */
export async function fetchWords(wordGroupId: string, page = 1): Promise<PaginatedResponse<Word>> {
    const { data } = await http.get<PaginatedResponse<Word>>(`/api/word-groups/${wordGroupId}/words`, {
        params: { page },
    });

    return data;
}

export async function createWord(wordGroupId: string, payload: WordPayload): Promise<Word> {
    const { data } = await http.post<ResourceResponse<Word>>(`/api/word-groups/${wordGroupId}/words`, payload);

    return data.data;
}

export async function updateWord(id: string, payload: WordPayload): Promise<Word> {
    const { data } = await http.patch<ResourceResponse<Word>>(`/api/words/${id}`, payload);

    return data.data;
}

export async function deleteWord(id: string): Promise<void> {
    await http.delete(`/api/words/${id}`);
}
