<?php

namespace App\Enums;

enum AccessRequestStatus: string
{
    /** Waiting for the creator's decision. */
    case Pending = 'pending';

    /** The requester was granted access. */
    case Accepted = 'accepted';

    /** The creator refused; the requester may ask again. */
    case Refused = 'refused';
}
