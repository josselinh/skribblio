<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListCollectionTargetsRequest;
use App\Http\Resources\GroupCollectionResource;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The collections a group can be added to by the current user, for the "Ajouter à une collection" button of a group
 * page: those they can add groups to, in the group's language, each flagged with `contains_word_group`.
 */
class WordGroupCollectionTargetController extends Controller
{
    /**
     * Maximum number of collections returned.
     */
    private const int LIMIT = 10;

    /**
     * With `group_collection_id`, only that collection when it is still a target (the one the SPA remembers). With `q`,
     * the collections whose name contains it (names starting with it first, then the user's own collections). Otherwise,
     * suggestions: the user's favorite collections (most recently favorited first) or, when none is a target, their own
     * collections (most recently updated first). `meta.suggestions` tells which (`favorites` or `own`).
     */
    public function __invoke(ListCollectionTargetsRequest $request, WordGroup $wordGroup): AnonymousResourceCollection
    {
        $user = $request->user();

        if ($request->filled('group_collection_id')) {
            return GroupCollectionResource::collection(
                $this->targets($user, $wordGroup)->whereKey($request->validated('group_collection_id'))->get(),
            );
        }

        $search = $request->search();
        if ($search !== '') {
            return GroupCollectionResource::collection($this->targets($user, $wordGroup)
                ->nameContains($search)
                ->orderByNameRelevance($search)
                ->orderByRaw('group_collections.created_by = ? desc', [$user->getKey()])
                ->orderBy('group_collections.name')
                ->limit(self::LIMIT)
                ->get());
        }

        $favorites = $this->targets($user, $wordGroup)
            ->whereHas('favoritedBy', fn (Builder $query) => $query->whereKey($user->getKey()))
            ->orderByDesc(fn ($query) => $query->from('favorite_group_collections')
                ->select('created_at')
                ->whereColumn('group_collection_id', 'group_collections.id')
                ->where('user_id', $user->getKey()))
            ->limit(self::LIMIT)
            ->get();
        if ($favorites->isNotEmpty()) {
            return GroupCollectionResource::collection($favorites)->additional(['meta' => ['suggestions' => 'favorites']]);
        }

        return GroupCollectionResource::collection($this->targets($user, $wordGroup)
            ->where('group_collections.created_by', $user->getKey())
            ->latest('group_collections.updated_at')
            ->limit(self::LIMIT)
            ->get())->additional(['meta' => ['suggestions' => 'own']]);
    }

    /**
     * The collections the user can add the group to, with what the picker shows.
     *
     * @return Builder<GroupCollection>
     */
    private function targets(User $user, WordGroup $wordGroup): Builder
    {
        return GroupCollection::query()
            ->acceptingContributionsFrom($user)
            ->inLanguage($wordGroup->language)
            ->with('creator')
            ->withCount('wordGroups')
            ->withIsFavorite($user)
            ->withExists(['wordGroups as contains_word_group' => fn (Builder $query) => $query->whereKey($wordGroup->getKey())]);
    }
}
