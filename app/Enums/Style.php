<?php

namespace App\Enums;

/**
 * Visual style of the interface chosen by a user: sober by default, or colourful (rounded font, vivid colours,
 * outlined cards). It combines with the colour theme.
 */
enum Style: string
{
    case Sober = 'sober';

    case Colorful = 'colorful';
}
