<?php

namespace App\Enums;

/**
 * Who created the groups or collections returned by a search.
 */
enum AuthorFilter: string
{
    case All = 'all';
    case Me = 'me';
    case Others = 'others';
}
