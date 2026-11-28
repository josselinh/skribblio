<?php

namespace App\Http\Controllers;

use App\Enums\InterfaceLocale;
use App\Http\Pages\IndexedLocales;
use App\Http\Pages\PageMeta;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

/**
 * Serves the HTML shell of the SPA, with the meta tags of each page: the public pages (home, Explorer, public
 * groups and collections) can be indexed, every other page is `noindex`. A path the SPA does not know, or a
 * group or collection the user cannot see, answers 404 with the same shell (the SPA shows its "not found" page).
 * An interface language asked with `?hl` (see `IndexedLocales`) sets the language of the texts and is kept by the
 * canonical address.
 */
class PageController extends Controller
{
    /**
     * How many words or groups the description of a group or collection names.
     */
    private const int DESCRIPTION_ITEMS = 8;

    public function home(Request $request): Response
    {
        return $this->render(new PageMeta(path: '/', indexable: true, interfaceLocale: $this->interfaceLocale($request)));
    }

    /**
     * The Explorer is indexed as a catalogue, without its filters; a search (`q`) is not.
     */
    public function explore(Request $request): Response
    {
        $interfaceLocale = $this->interfaceLocale($request);

        return $this->render(new PageMeta(
            title: __('seo.explore.title'),
            description: __('seo.explore.description'),
            path: '/explore',
            indexable: ! $request->filled('q'),
            interfaceLocale: $interfaceLocale,
        ));
    }

    /**
     * Named after the group and described by its best scored words, in the group's language. Only a public
     * group with words is indexed.
     */
    public function wordGroup(Request $request, string $wordGroupId): Response
    {
        $interfaceLocale = $this->interfaceLocale($request);
        $wordGroup = Str::isUuid($wordGroupId) ? WordGroup::query()->withCount('words')->find($wordGroupId) : null;
        if ($wordGroup === null || ! $wordGroup->isVisibleTo($this->viewer($request))) {
            return $this->notFound();
        }

        $locale = $wordGroup->language->value;
        $description = null;
        if ($wordGroup->words_count > 0) {
            $names = $wordGroup->words()->select('name')->withScore()
                ->orderByDesc('score')->orderBy('name')
                ->limit(self::DESCRIPTION_ITEMS)->pluck('name');
            $description = trans_choice('seo.word_group.description', $wordGroup->words_count, [
                'words' => $this->enumerate($names->all(), $wordGroup->words_count),
            ], $locale);
        }

        return $this->render(new PageMeta(
            title: $wordGroup->name,
            description: $description,
            path: "/groups/{$wordGroup->getKey()}",
            indexable: ! $wordGroup->isPrivate() && $wordGroup->words_count > 0,
            locale: $locale,
            interfaceLocale: $interfaceLocale,
        ));
    }

    /**
     * Named after the collection and described by the groups the viewer can see (the public ones for search
     * engines), in the collection's language. Only a public collection showing groups is indexed.
     */
    public function groupCollection(Request $request, string $groupCollectionId): Response
    {
        $interfaceLocale = $this->interfaceLocale($request);
        $viewer = $this->viewer($request);
        $groupCollection = Str::isUuid($groupCollectionId) ? GroupCollection::query()->find($groupCollectionId) : null;
        if ($groupCollection === null || ! $groupCollection->isVisibleTo($viewer)) {
            return $this->notFound();
        }

        $locale = $groupCollection->language->value;
        $visibleGroups = $groupCollection->wordGroups()->visibleTo($viewer);
        $groupsCount = (clone $visibleGroups)->count();
        $description = null;
        if ($groupsCount > 0) {
            $names = $visibleGroups->orderBy('word_groups.name')->limit(self::DESCRIPTION_ITEMS)->pluck('word_groups.name');
            $description = trans_choice('seo.group_collection.description', $groupsCount, [
                'groups' => $this->enumerate($names->all(), $groupsCount),
            ], $locale);
        }

        return $this->render(new PageMeta(
            title: $groupCollection->name,
            description: $description,
            path: "/collections/{$groupCollection->getKey()}",
            indexable: ! $groupCollection->isPrivate() && $groupsCount > 0,
            locale: $locale,
            interfaceLocale: $interfaceLocale,
        ));
    }

    /**
     * The pages of the application itself (dashboard, account, authentication…), never indexed.
     */
    public function application(Request $request): Response
    {
        $this->interfaceLocale($request);

        return $this->render(new PageMeta);
    }

    public function fallback(Request $request): Response
    {
        abort_if($request->is('api/*') || $request->expectsJson(), 404);
        $this->interfaceLocale($request);

        return $this->notFound();
    }

    /**
     * The interface language asked with `?hl`, which then sets the language of the texts; otherwise the default
     * one, the texts keeping the current language (the logged-in user's, see `SetLocale`).
     */
    private function interfaceLocale(Request $request): InterfaceLocale
    {
        $requested = InterfaceLocale::tryFrom((string) $request->query(IndexedLocales::QUERY_PARAMETER));
        if ($requested === null) {
            return IndexedLocales::default();
        }
        App::setLocale($requested->value);

        return $requested;
    }

    private function notFound(): Response
    {
        return $this->render(new PageMeta, 404);
    }

    private function render(PageMeta $meta, int $status = 200): Response
    {
        return response()->view('app', ['meta' => $meta], $status);
    }

    /**
     * The user the public pages are shown to: a user whose email address is not verified is a guest, as for
     * the public API (`AuthenticateIfVerified`).
     */
    private function viewer(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User && $user->hasVerifiedEmail() ? $user : null;
    }

    /**
     * "a, b, c", followed by an ellipsis when there are more than the names given.
     *
     * @param  list<string>  $names
     */
    private function enumerate(array $names, int $total): string
    {
        return implode(', ', $names).($total > count($names) ? '…' : '');
    }
}
