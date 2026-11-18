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
    /** Only present in search results. */
    creator?: Pick<User, 'id' | 'name'>;
    /** Only present in search results. */
    words_count?: number;
    created_at: string | null;
    updated_at: string | null;
    /** Whether the current user favorited it; absent on some endpoints. */
    is_favorite?: boolean;
    /** Only in favorites lists: false when it became private and the user can no longer open it. */
    is_accessible?: boolean;
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

export interface GroupCollection {
    id: string;
    name: string;
    visibility: Visibility;
    /** Only present in search results. */
    creator?: Pick<User, 'id' | 'name'>;
    /** Only present in search results. */
    word_groups_count?: number;
    created_at: string | null;
    updated_at: string | null;
    /** Whether the current user favorited it; absent on some endpoints. */
    is_favorite?: boolean;
    /** Only in favorites lists: false when it became private and the user can no longer open it. */
    is_accessible?: boolean;
    /** Actions the current user may perform on the collection. */
    can: {
        update: boolean;
    };
}

/** A collection as returned by its detail endpoint. */
export interface GroupCollectionDetail extends GroupCollection {
    /** Only the groups the current user can see. */
    word_groups: WordGroup[];
    /** Groups of the collection hidden from the current user (private groups of other users). */
    hidden_word_groups_count: number;
}

/** Who created the groups or collections returned by a search. */
export type AuthorFilter = 'all' | 'me' | 'others';

export type FavoriteKind = 'word-groups' | 'group-collections';
