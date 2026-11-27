<?php

namespace App\Enums;

/**
 * Visual style of the interface chosen by a user: colourful by default (rounded font, vivid colours,
 * outlined cards), or sober. It combines with the colour theme.
 */
enum Style: string
{
    case Sober = 'sober';

    case Colorful = 'colorful';
}
