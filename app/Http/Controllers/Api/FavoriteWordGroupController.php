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
     * List the favorite groups, most recently favorited first. Groups the user can no longer see (made private,
     * access revoked) are left out, but stay in the favorites: they show again once the user regains access.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $wordGroups = $user->favoriteWordGroups()
            ->visibleTo($user)
            ->with('creator')
            ->withCount('words')
            ->orderByPivot('created_at', 'desc')
            ->latest('word_groups.id')
            ->paginate(self::PER_PAGE)
            ->through(fn (WordGroup $wordGroup) => $wordGroup->forceFill(['is_favorite' => true]));

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
