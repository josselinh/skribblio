<?php

namespace App\Enums;

/**
 * Order of the groups or collections returned by a search, after the name relevance.
 */
enum SearchSort: string
{
    /** Most recently updated first. */
    case Recent = 'recent';

    /** Most favorited first. */
    case Popular = 'popular';

    /** Most words (groups) or most groups (collections) first. */
    case Largest = 'largest';

    /** Alphabetical order. */
    case Name = 'name';
}
