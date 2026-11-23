<?php

namespace App\Enums;

/**
 * Colour theme of the interface chosen by a user: the system's one by default, or forced light or dark.
 */
enum Theme: string
{
    case System = 'system';

    case Light = 'light';

    case Dark = 'dark';
}
