<?php

namespace App\Http\Controllers;

use App\Http\Pages\IndexedLocales;
use App\Models\GroupCollection;
use App\Models\WordGroup;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use XMLWriter;

/**
 * The sitemap for search engines: the home page, the Explorer and the pages that can be indexed (see
 * `PageController`), public groups with words and public collections showing public groups, most recently
 * updated first. Each page is listed in every indexed interface language (`IndexedLocales`), naming the others.
 */
class SitemapController extends Controller
{
    /**
     * The most URLs a sitemap may list.
     */
    public const int MAX_URLS = 50000;

    public function __invoke(): Response
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

        $this->writePage($xml, '/');
        $this->writePage($xml, '/explore');
        $remaining = intdiv(self::MAX_URLS, count(IndexedLocales::all())) - 2;

        $wordGroups = WordGroup::query()->visibleTo(null)->whereHas('words')
            ->latest('updated_at')->limit($remaining)->get(['id', 'updated_at']);
        foreach ($wordGroups as $wordGroup) {
            $this->writePage($xml, "/groups/{$wordGroup->getKey()}", $wordGroup->updated_at);
        }
        $remaining -= $wordGroups->count();

        if ($remaining > 0) {
            $groupCollections = GroupCollection::query()->visibleTo(null)
                ->whereHas('wordGroups', fn (Builder $query) => $query->visibleTo(null))
                ->latest('updated_at')->limit($remaining)->get(['id', 'updated_at']);
            foreach ($groupCollections as $groupCollection) {
                $this->writePage($xml, "/collections/{$groupCollection->getKey()}", $groupCollection->updated_at);
            }
        }

        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Lists a page once per indexed language, each entry naming every language version.
     */
    private function writePage(XMLWriter $xml, string $path, ?Carbon $lastModified = null): void
    {
        $alternates = IndexedLocales::alternates($path);
        foreach (IndexedLocales::all() as $locale) {
            $xml->startElement('url');
            $xml->writeElement('loc', IndexedLocales::url($path, $locale));
            if ($lastModified !== null) {
                $xml->writeElement('lastmod', $lastModified->toAtomString());
            }
            foreach ($alternates as $hreflang => $alternateUrl) {
                $xml->startElement('xhtml:link');
                $xml->writeAttribute('rel', 'alternate');
                $xml->writeAttribute('hreflang', $hreflang);
                $xml->writeAttribute('href', $alternateUrl);
                $xml->endElement();
            }
            $xml->endElement();
        }
    }
}
