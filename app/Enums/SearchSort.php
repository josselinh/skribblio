<?php

namespace App\Enums;

/**
 * Order of the groups or collections returned by a search, after the name relevance.
 */
enum SearchSort: string
{
    /** Most recently updated first by default. */
    case Recent = 'recent';

    /** Most favorited first by default. */
    case Popular = 'popular';

    /** Most words (groups) or most groups (collections) first by default. */
    case Largest = 'largest';

    /** Alphabetical order by default. */
    case Name = 'name';

    /**
     * Direction used when none is given: alphabetical for the name, most recent, popular or largest first otherwise.
     */
    public function defaultDirection(): SortDirection
    {
        return match ($this) {
            self::Name => SortDirection::Asc,
            self::Recent, self::Popular, self::Largest => SortDirection::Desc,
        };
    }
}
