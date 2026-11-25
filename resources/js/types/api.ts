import type { GroupCollection, Word } from '@/types/models';

/** Shape of a Laravel API Resource response. */
export interface ResourceResponse<T> {
    data: T;
}

/** Shape of a paginated Laravel API Resource collection. */
export interface PaginatedResponse<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
}

/** Response of `POST /api/word-groups/{id}/words`. */
/** Words of a group. */
export type WordsResponse = PaginatedResponse<Word>;

export interface CreateWordsResponse {
    /** Added words, in the order they were submitted. */
    data: Word[];
    /** Names already in the group (or repeated in the submission), not added. */
    skipped: string[];
    /** Names that cannot be words, with the reason. */
    invalid: { name: string; message: string }[];
}

/** Shape of a Laravel validation error response (HTTP 422). */
export interface ValidationErrorResponse {
    message: string;
    errors: Record<string, string[]>;
}

/** Shape of the status responses returned by Fortify (password reset, etc.). */
export interface MessageResponse {
    message: string;
}

/**
 * Collections a group can be added to (`GET /api/word-groups/{id}/collection-targets`). Without a search, `meta.suggestions`
 * tells whether they are the user's favorite collections or, when no favorite can receive the group, their own ones.
 */
export interface CollectionTargetsResponse {
    data: GroupCollection[];
    meta?: { suggestions: 'favorites' | 'own' };
}
