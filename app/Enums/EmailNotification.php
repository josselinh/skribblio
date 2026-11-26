<?php

namespace App\Enums;

/**
 * Kinds of emails a user can choose to receive or not (`users.email_notifications`). None of them is sent
 * until the user turns it on; account emails (verification, password reset) are always sent.
 */
enum EmailNotification: string
{
    /** Someone asked to access, or to contribute to, one of the user's groups (also from a collection). */
    case WordGroupAccessRequested = 'word_group_access_requested';

    /** Someone asked to access, or to contribute to, one of the user's collections. */
    case GroupCollectionAccessRequested = 'group_collection_access_requested';

    /** The creator accepted or refused one of the user's access or contribution requests. */
    case AccessRequestDecided = 'access_request_decided';

    /** The creator of a group or collection the user is a member of changed their type of sharing. */
    case ShareTypeChanged = 'share_type_changed';

    /** Someone added one of the user's groups to their favorites. */
    case WordGroupFavorited = 'word_group_favorited';

    /** Someone added one of the user's collections to their favorites. */
    case GroupCollectionFavorited = 'group_collection_favorited';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
