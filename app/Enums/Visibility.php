<?php

namespace App\Enums;

enum Visibility: string
{
    /** Visible to every logged-in user. */
    case Public = 'public';

    /** Visible to its creator only. */
    case Private = 'private';
}
