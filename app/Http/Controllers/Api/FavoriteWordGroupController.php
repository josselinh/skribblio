<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WordGroupResource;
use App\Models\WordGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * The current user's favorite word groups.
 */
class FavoriteWordGroupController extends Controller
{
    /**
     * Number of groups per page in listings.
     */
    private const int PER_PAGE = 12;

    /**
     * List the favorite groups, most recently favorited first. Groups that became private are still
     * listed, flagged with `is_accessible: false`, so that the user can see and remove them.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $wordGroups = $user->favoriteWordGroups()
            ->with('creator')
            ->withCount('words')
            ->orderByPivot('created_at', 'desc')
            ->latest('word_groups.id')
            ->paginate(self::PER_PAGE);

        $wordGroups->getCollection()->each(fn (WordGroup $wordGroup) => $wordGroup->forceFill([
            'is_favorite' => true,
            'is_accessible' => $wordGroup->isVisibleTo($user),
        ]));

        return WordGroupResource::collection($wordGroups);
    }

    /**
     * Add a group to the favorites. Adding it again does nothing.
     */
    public function update(Request $request, WordGroup $wordGroup): Response
    {
        Gate::authorize('favorite', $wordGroup);

        $wordGroup->addToFavoritesOf($request->user());

        return response()->noContent();
    }

    /**
     * Remove a group from the favorites, even if it is no longer visible. Removing it again does nothing.
     */
    public function destroy(Request $request, WordGroup $wordGroup): Response
    {
        $request->user()->favoriteWordGroups()->detach($wordGroup);

        return response()->noContent();
    }
}
