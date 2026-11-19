import type { Word } from '@/types/models';

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
/** Words of a group; `has_votes` tells whether any of its words received a vote. */
export interface WordsResponse extends PaginatedResponse<Word> {
    has_votes: boolean;
}

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
