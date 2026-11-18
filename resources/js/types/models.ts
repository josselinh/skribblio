export interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string | null;
}

export type Visibility = 'public' | 'private';

export interface WordGroup {
    id: string;
    name: string;
    visibility: Visibility;
    created_at: string | null;
    updated_at: string | null;
    /** Actions the current user may perform on the group. */
    can: {
        update: boolean;
        add_word: boolean;
    };
}

export interface Word {
    id: string;
    name: string;
    creator: Pick<User, 'id' | 'name'>;
    created_at: string | null;
    updated_at: string | null;
    /** Actions the current user may perform on the word. */
    can: {
        update: boolean;
        delete: boolean;
    };
}
