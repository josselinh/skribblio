<?php

namespace App\Enums;

/**
 * Column by which the words of a group are listed.
 */
enum WordSort: string
{
    case Name = 'name';

    /** Name of the author; words whose author deleted their account come last. */
    case Author = 'author';

    case Created = 'created';

    case Updated = 'updated';

    /** Upvotes minus downvotes, then most upvotes. */
    case Score = 'score';

    /**
     * Direction used when none is given: alphabetical for text, most recent or best first otherwise.
     */
    public function defaultDirection(): SortDirection
    {
        return match ($this) {
            self::Name, self::Author => SortDirection::Asc,
            self::Created, self::Updated, self::Score => SortDirection::Desc,
        };
    }
}
