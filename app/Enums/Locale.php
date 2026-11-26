<?php

namespace App\Enums;

/**
 * Languages of the content: the language of the words of a group or of the groups of a collection (`language`),
 * and the one a user prefers to browse (`users.content_locale`). The interface is available in more languages:
 * see `InterfaceLocale`.
 */
enum Locale: string
{
    case Fr = 'fr';

    case En = 'en';

    /**
     * Name of the language in the current interface language, e.g. "anglais".
     */
    public function label(): string
    {
        return __("languages.{$this->value}");
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
