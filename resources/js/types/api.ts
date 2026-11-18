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

/** Shape of a Laravel validation error response (HTTP 422). */
export interface ValidationErrorResponse {
    message: string;
    errors: Record<string, string[]>;
}

/** Shape of the status responses returned by Fortify (password reset, etc.). */
export interface MessageResponse {
    message: string;
}
