<?php

namespace App\Enums;

/**
 * What the creator of a shared group or collection lets one of its members do, while the item is open to
 * contributions. Name, visibility, options, sharing and deletion always stay with the creator.
 */
enum ShareType: string
{
    /** The member adds content and changes or removes their own contributions only. */
    case Partial = 'partial';

    /** The member also changes or removes the contributions of everyone else, the creator's included. */
    case Full = 'full';
}
