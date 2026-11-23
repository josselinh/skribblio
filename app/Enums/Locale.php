<?php

namespace App\Enums;

/**
 * Languages the interface is available in. The user's choice (`users.locale`) also sets the language of the
 * emails they receive; visitors send the one of their device in the `X-Locale` header.
 */
enum Locale: string
{
    case Fr = 'fr';

    case En = 'en';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
