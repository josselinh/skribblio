<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\GroupCollectionResource;
use App\Http\Resources\WordGroupResource;
use App\Models\GroupCollection;
use App\Models\WordGroup;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Searches, by name, the groups and collections the current user can see (their own and public ones).
 * Names starting with the search come first, then the most recently updated.
 */
class SearchController extends Controller
{
    public function wordGroups(SearchRequest $request): AnonymousResourceCollection
    {
        $wordGroups = WordGroup::visibleTo($request->user())
            ->createdByAuthor($request->author(), $request->user())
            ->nameContains($request->search())
            ->with('creator')
            ->withCount('words')
            ->withIsFavorite($request->user())
            ->orderByNameRelevance($request->search())
            ->latest('updated_at')
            ->latest('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return WordGroupResource::collection($wordGroups);
    }

    public function groupCollections(SearchRequest $request): AnonymousResourceCollection
    {
        $groupCollections = GroupCollection::visibleTo($request->user())
            ->createdByAuthor($request->author(), $request->user())
            ->nameContains($request->search())
            ->with('creator')
            ->withCount('wordGroups')
            ->withIsFavorite($request->user())
            ->orderByNameRelevance($request->search())
            ->latest('updated_at')
            ->latest('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return GroupCollectionResource::collection($groupCollections);
    }
}
