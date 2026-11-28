/** Colour theme of the interface: the system's one, or forced light or dark. */
export type Theme = 'system' | 'light' | 'dark';

/** Visual style of the interface, combined with the theme: colourful by default (rounded font, vivid colours), or sober. */
export type Style = 'sober' | 'colorful';

/** A language of the content (the words of a group, the groups of a collection). */
export type Locale = 'en' | 'fr' | 'de';

/** A language of the interface (and of the emails), chosen independently of the content's: neither includes the other. */
export type InterfaceLocale = 'en' | 'fr' | 'zh';

/** Kinds of emails the user chooses to receive or not (account emails are always sent). */
export type EmailNotification =
    | 'word_group_access_requested'
    | 'group_collection_access_requested'
    | 'access_request_decided'
    | 'share_type_changed'
    | 'word_group_favorited'
    | 'group_collection_favorited';

/** The current user (GET /api/user). Other users are only exposed as `Pick<User, 'id' | 'name'>`. */
export interface User {
    id: string;
    /** Pseudo, shown to the other users. */
    name: string;
    email: string;
    email_verified_at: string | null;
    theme: Theme;
    style: Style;
    /** Language of the interface and of the emails. */
    locale: InterfaceLocale;
    /** Language of the lists the user browses by default (Explorer). */
    content_locale: Locale;
    /** Whether each kind of email is sent to the user. */
    email_notifications: Record<EmailNotification, boolean>;
    created_at: string | null;
}

export type Visibility = 'public' | 'private';

export interface WordGroup {
    id: string;
    name: string;
    /** Language of its words. */
    language: Locale;
    visibility: Visibility;
    /** Whether the users who can see it can add words (and manage theirs). */
    accepts_contributions: boolean;
    /** While public and open: only its members (accepted through the share link) contribute. */
    contributions_members_only: boolean;
    /** Present in search results and on the detail endpoint. */
    creator?: Pick<User, 'id' | 'name'>;
    /** Only present in search results. */
    words_count?: number;
    /** Only when listed by a collection, for logged-in users: who added it to the collection (null once they were deleted). */
    added_by?: Pick<User, 'id' | 'name'> | null;
    /** Only when listed by a collection: when it was added to the collection (null for old additions). */
    added_at?: string | null;
    created_at: string | null;
    updated_at: string | null;
    /** Whether the current user favorited it; absent on some endpoints. */
    is_favorite?: boolean;
    /** Actions the current user may perform on the group. */
    can: {
        update: boolean;
        delete: boolean;
        share: boolean;
        manage_access: boolean;
        /** Whether the user is a member (accepted through the share link) who can give up their access. */
        leave: boolean;
        add_word: boolean;
        /** Whether the user changes and deletes every word, not only their own (creator, or member with a full sharing). */
        manage_contributions: boolean;
        duplicate: boolean;
        /** Only when listed through a collection: whether the user can remove it from that collection. */
        remove_from_collection?: boolean;
    };
}

export interface Word {
    id: string;
    name: string;
    /** Null once the author deleted their account; left out for visitors, who do not see the users' names. */
    creator?: Pick<User, 'id' | 'name'> | null;
    /**
     * Who renamed the word last: null when it was never renamed, renamed before this was recorded, or by an account since
     * deleted; left out for visitors.
     */
    updater?: Pick<User, 'id' | 'name'> | null;
    upvotes_count: number;
    downvotes_count: number;
    /** The current user's vote, null when they did not vote. */
    my_vote: WordVote | null;
    created_at: string | null;
    updated_at: string | null;
    /** Actions the current user may perform on the word. */
    can: {
        update: boolean;
        delete: boolean;
        /** Open group, visible to the user, and the word is not theirs. */
        vote: boolean;
    };
}

export type WordVote = 'up' | 'down';

/** Column by which the words of a group are listed (`App\Enums\WordSort`). */
export type WordSort = 'name' | 'author' | 'created' | 'updated' | 'score';

export type SortDirection = 'asc' | 'desc';

export interface GroupCollection {
    id: string;
    name: string;
    /** Language of its groups, which are all in it. */
    language: Locale;
    visibility: Visibility;
    /** Whether the users who can see it can add groups (and remove the ones they added). */
    accepts_contributions: boolean;
    /** While public and open: only its members (accepted through the share link) contribute. */
    contributions_members_only: boolean;
    /** Present in search results and on the detail endpoint. */
    creator?: Pick<User, 'id' | 'name'>;
    /** Only present in search results. */
    word_groups_count?: number;
    created_at: string | null;
    updated_at: string | null;
    /** Whether the current user favorited it; absent on some endpoints. */
    is_favorite?: boolean;
    /** Only among the collections a group can be added to: whether it already contains that group. */
    contains_word_group?: boolean;
    /** Actions the current user may perform on the collection. */
    can: {
        update: boolean;
        delete: boolean;
        share: boolean;
        manage_access: boolean;
        /** Whether the user is a member (accepted through the share link) who can give up their access. */
        leave: boolean;
        add_word_group: boolean;
        /** Whether the user removes every group, not only those they added (creator, or member with a full sharing). */
        manage_contributions: boolean;
        duplicate: boolean;
    };
}

/** A collection as returned by its detail endpoint. */
export interface GroupCollectionDetail extends GroupCollection {
    /** Only the groups the current user can see. */
    word_groups: WordGroup[];
}

/** Who created the groups or collections returned by a search. */
export type AuthorFilter = 'all' | 'me' | 'others';

/** Order of search results after the name relevance: recently updated, most favorited, largest, alphabetical. */
export type SearchSort = 'recent' | 'popular' | 'largest' | 'name';

export type FavoriteKind = 'word-groups' | 'group-collections';

/**
 * What a share link gives: `access` to a private item, `contribution` to a public item open to its
 * members only.
 */
export type AccessPurpose = 'access' | 'contribution';

/** An access (or contribution) request received by the creator of a group or collection. */
export interface AccessRequest {
    id: string;
    kind: FavoriteKind;
    purpose: AccessPurpose;
    item: { id: string; name: string };
    requester: Pick<User, 'id' | 'name'>;
    /** Set when the request was sent from a collection containing the group. */
    via_group_collection: { id: string; name: string } | null;
    created_at: string | null;
}

/** What a user sees when opening a share link. */
export interface ShareInvitation {
    kind: FavoriteKind;
    /** Only when the user can see the item. */
    id?: string;
    name: string;
    creator: { name: string };
    purpose: AccessPurpose;
    /** `accessible`: the user already has what the link gives. */
    status: 'accessible' | 'pending' | 'refused' | 'none';
}

/**
 * What the creator lets a member do while the item is open to contributions (`App\Enums\ShareType`): manage
 * their own contributions only, or everyone's.
 */
export type ShareType = 'partial' | 'full';

/** A member of a shared item, as listed to its creator. */
export interface ItemMember extends Pick<User, 'id' | 'name'> {
    share_type: ShareType;
    granted_at: string | null;
}

/** Pending requests and members of a shared item, for its creator. */
export interface ItemAccess {
    pending_requests: AccessRequest[];
    members: ItemMember[];
}
