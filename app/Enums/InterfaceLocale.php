<?php

namespace App\Enums;

/**
 * Languages the interface is available in. The user's choice (`users.locale`) also sets the language of the
 * emails they receive; visitors send the one of their device in the `X-Locale` header.
 *
 * The content (the words of the groups) is offered in other languages: see `Locale`.
 */
enum InterfaceLocale: string
{
    case Fr = 'fr';

    case En = 'en';

    case Zh = 'zh';

    /**
     * The language of the lists shown by default with this interface language: the default content language of a
     * user registering in it. Keep in sync with `CONTENT_LOCALE_BY_INTERFACE_LOCALE` of `@/lib/contentLanguage`.
     */
    public function contentLocale(): Locale
    {
        return match ($this) {
            self::En => Locale::En,
            self::Fr => Locale::Fr,
            self::Zh => Locale::Fr,
        };
    }
}
