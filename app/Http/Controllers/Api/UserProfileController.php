<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupCollectionResource;
use App\Http\Resources\WordGroupResource;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Another user's page, as seen by the current user: their name, and the groups and collections they
 * created that the current user can see (public ones and private ones shared with the current user).
 * The email address is never exposed.
 */
class UserProfileController extends Controller
{
    /**
     * Number of items per page in listings.
     */
    private const int PER_PAGE = 12;

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => ['id' => $user->id, 'name' => $user->name]]);
    }

    public function wordGroups(Request $request, User $user): AnonymousResourceCollection
    {
        $wordGroups = WordGroup::visibleTo($request->user())
            ->where('created_by', $user->getKey())
            ->withCount('words')
            ->withIsFavorite($request->user())
            ->latest('updated_at')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return WordGroupResource::collection($wordGroups);
    }

    public function groupCollections(Request $request, User $user): AnonymousResourceCollection
    {
        $groupCollections = GroupCollection::visibleTo($request->user())
            ->where('created_by', $user->getKey())
            ->withCount('wordGroups')
            ->withIsFavorite($request->user())
            ->latest('updated_at')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return GroupCollectionResource::collection($groupCollections);
    }
}
