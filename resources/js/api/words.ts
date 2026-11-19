import { http } from '@/lib/http';
import type { CreateWordsResponse, ResourceResponse, WordsResponse } from '@/types/api';
import type { SortDirection, Word, WordSort, WordVote } from '@/types/models';

export interface WordPayload {
    name: string;
}

export interface WordOrder {
    sort: WordSort;
    direction: SortDirection;
}

/** Words of a group with their votes, in the given order (most recently added first by default). */
export async function fetchWords(wordGroupId: string, page = 1, order?: WordOrder): Promise<WordsResponse> {
    const { data } = await http.get<WordsResponse>(`/api/word-groups/${wordGroupId}/words`, {
        params: { page, ...order },
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

/** Records the current user's vote (null removes it) and returns the word with its updated counts. */
export async function voteForWord(id: string, vote: WordVote | null): Promise<Word> {
    const { data } =
        vote === null
            ? await http.delete<ResourceResponse<Word>>(`/api/words/${id}/vote`)
            : await http.put<ResourceResponse<Word>>(`/api/words/${id}/vote`, { vote });

    return data.data;
}
