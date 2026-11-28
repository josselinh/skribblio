<?php

namespace App\Http\Pages;

use App\Enums\InterfaceLocale;

/**
 * What the HTML shell of a page tells search engines and link previews before the SPA runs: its title, its
 * description, its canonical address, its language versions and whether it may be indexed. The SPA sets the
 * same title afterwards (`usePageTitle()`), so that both agree.
 */
final readonly class PageMeta
{
    /**
     * @param  ?string  $path  the page's path, without query, for its canonical address; none for a page that is not indexed
     * @param  ?string  $locale  language of the texts (a group's or collection's own); the current one when null
     * @param  InterfaceLocale  $interfaceLocale  language of the interface asked with `?hl`, which the canonical address keeps
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $path = null,
        public bool $indexable = false,
        public ?string $locale = null,
        public InterfaceLocale $interfaceLocale = InterfaceLocale::En,
    ) {}

    /**
     * The `<title>` of the page: "Name · Skribblio Tools", or the site's name alone.
     */
    public function documentTitle(): string
    {
        $siteName = $this->siteName();

        return $this->title === null ? $siteName : "{$this->title} · {$siteName}";
    }

    public function siteName(): string
    {
        return __('seo.site_name', locale: $this->locale);
    }

    public function descriptionText(): string
    {
        return $this->description ?? __('seo.description', locale: $this->locale);
    }

    public function canonicalUrl(): ?string
    {
        return $this->path === null ? null : IndexedLocales::url($this->path, $this->interfaceLocale);
    }

    /**
     * The address of each language version, by `hreflang`, for an indexed page.
     *
     * @return array<string, string>
     */
    public function alternateUrls(): array
    {
        return $this->indexable && $this->path !== null ? IndexedLocales::alternates($this->path) : [];
    }
}
