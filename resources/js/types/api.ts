/** Shape of a Laravel API Resource response. */
export interface ResourceResponse<T> {
    data: T;
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
