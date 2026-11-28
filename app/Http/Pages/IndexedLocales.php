<?php

namespace App\Http\Pages;

use App\Enums\InterfaceLocale;

/**
 * The interface languages search engines index: English, the default, at the plain address of a page, and French
 * with `?hl=fr`, which the SPA reads to open in that language (`hl` as `lang` is the Explorer's content filter).
 * Each version names the others (`hreflang`); the plain address is also the default one (`x-default`), where the
 * SPA follows the visitor's device.
 */
final class IndexedLocales
{
    public const string QUERY_PARAMETER = 'hl';

    /**
     * @return list<InterfaceLocale>
     */
    public static function all(): array
    {
        return [InterfaceLocale::En, InterfaceLocale::Fr];
    }

    public static function default(): InterfaceLocale
    {
        return InterfaceLocale::En;
    }

    /**
     * The address of a page in one of the indexed languages; any other language gets the default address.
     */
    public static function url(string $path, InterfaceLocale $locale): string
    {
        return $locale === self::default() || ! in_array($locale, self::all(), true)
            ? url($path)
            : url($path).'?'.self::QUERY_PARAMETER.'='.$locale->value;
    }

    /**
     * The address of each language version of a page, by `hreflang`, `x-default` last.
     *
     * @return array<string, string>
     */
    public static function alternates(string $path): array
    {
        $alternates = [];
        foreach (self::all() as $locale) {
            $alternates[$locale->value] = self::url($path, $locale);
        }
        $alternates['x-default'] = url($path);

        return $alternates;
    }
}
