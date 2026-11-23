<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupCollectionResource;
use App\Models\GroupCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * The current user's favorite collections.
 */
class FavoriteGroupCollectionController extends Controller
{
    /**
     * Number of collections per page in listings.
     */
    private const int PER_PAGE = 12;

    /**
     * List the favorite collections, most recently favorited first. Collections that became private
     * are still listed, flagged with `is_accessible: false`, so that the user can see and remove them.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $groupCollections = $user->favoriteGroupCollections()
            ->with('creator')
            ->withCount('wordGroups')
            ->orderByPivot('created_at', 'desc')
            ->latest('group_collections.id')
            ->paginate(self::PER_PAGE);

        $groupCollections->getCollection()->each(fn (GroupCollection $groupCollection) => $groupCollection->forceFill([
            'is_favorite' => true,
            'is_accessible' => $groupCollection->isVisibleTo($user),
        ]));

        return GroupCollectionResource::collection($groupCollections);
    }

    /**
     * Add a collection to the favorites. Adding it again does nothing.
     */
    public function update(Request $request, GroupCollection $groupCollection): Response
    {
        Gate::authorize('favorite', $groupCollection);

        $groupCollection->addToFavoritesOf($request->user());

        return response()->noContent();
    }

    /**
     * Remove a collection from the favorites, even if it is no longer visible. Removing it again does nothing.
     */
    public function destroy(Request $request, GroupCollection $groupCollection): Response
    {
        $request->user()->favoriteGroupCollections()->detach($groupCollection);

        return response()->noContent();
    }
}
