<?php

namespace App\Enums;

/**
 * Languages the interface is available in. The user's choice (`users.locale`) also sets the language of the
 * emails they receive; visitors send the one of their device in the `X-Locale` header.
 *
 * The content (the words of the groups) is only offered in some of them: see `Locale`.
 */
enum InterfaceLocale: string
{
    case Fr = 'fr';

    case En = 'en';

    case Zh = 'zh';

    /**
     * The content language matching this interface language, if the content is offered in it, e.g. the default
     * language of the lists of a user registering in it.
     */
    public function contentLocale(): ?Locale
    {
        return Locale::tryFrom($this->value);
    }
}
