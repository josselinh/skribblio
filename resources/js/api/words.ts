import { http } from '@/lib/http';
import type { CreateWordsResponse, PaginatedResponse, ResourceResponse } from '@/types/api';
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

/**
 * Adds several words at once. Words already in the group are skipped and invalid ones are
 * reported, without preventing the others from being added.
 */
export async function createWords(wordGroupId: string, names: string[]): Promise<CreateWordsResponse> {
    const { data } = await http.post<CreateWordsResponse>(`/api/word-groups/${wordGroupId}/words`, { names });

    return data;
}

export async function updateWord(id: string, payload: WordPayload): Promise<Word> {
    const { data } = await http.patch<ResourceResponse<Word>>(`/api/words/${id}`, payload);

    return data.data;
}

export async function deleteWord(id: string): Promise<void> {
    await http.delete(`/api/words/${id}`);
}
