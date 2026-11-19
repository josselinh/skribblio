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
    /** Only in the "shared with me" list: granted, or requested and waiting. */
    access_status?: AccessStatus;
    /** Actions the current user may perform on the group. */
    can: {
        update: boolean;
        share: boolean;
        manage_access: boolean;
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
    /** Only in the "shared with me" list: granted, or requested and waiting. */
    access_status?: AccessStatus;
    /** Actions the current user may perform on the collection. */
    can: {
        update: boolean;
        share: boolean;
        manage_access: boolean;
    };
}

/** A collection as returned by its detail endpoint. */
export interface GroupCollectionDetail extends GroupCollection {
    /** Only the groups the current user can see. */
    word_groups: WordGroup[];
    /** Groups of the collection hidden from the current user (private groups of other users). */
    hidden_word_groups_count: number;
    /** Hidden groups the current user already requested access to. */
    hidden_word_groups_requested_count: number;
}

/** Who created the groups or collections returned by a search. */
export type AuthorFilter = 'all' | 'me' | 'others';

/** Order of search results after the name relevance: recently updated, most favorited, largest, alphabetical. */
export type SearchSort = 'recent' | 'popular' | 'largest' | 'name';

export type FavoriteKind = 'word-groups' | 'group-collections';

export type AccessStatus = 'granted' | 'pending';

/** An access request received by the creator of a private group or collection. */
export interface AccessRequest {
    id: string;
    kind: FavoriteKind;
    item: { id: string; name: string };
    requester: Pick<User, 'id' | 'name'>;
    /** Set when the request was sent from a collection containing the group. */
    via_group_collection: { id: string; name: string } | null;
    created_at: string | null;
}

/** What a user sees when opening a share link. */
export interface ShareInvitation {
    kind: FavoriteKind;
    /** Only when the item is accessible. */
    id?: string;
    name: string;
    creator: { name: string };
    status: 'accessible' | 'pending' | 'refused' | 'none';
}

/** Pending requests and members of a private item, for its creator. */
export interface ItemAccess {
    pending_requests: AccessRequest[];
    members: (Pick<User, 'id' | 'name'> & { granted_at: string | null })[];
}
