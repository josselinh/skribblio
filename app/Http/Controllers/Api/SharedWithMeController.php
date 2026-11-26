<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupCollectionResource;
use App\Http\Resources\WordGroupResource;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Private groups and collections of other users that the current user was granted access to. Requests still
 * waiting for the creator's answer are not listed.
 */
class SharedWithMeController extends Controller
{
    /**
     * Number of items per page in listings.
     */
    private const int PER_PAGE = 12;

    public function wordGroups(Request $request): AnonymousResourceCollection
    {
        $wordGroups = $this->sharedWith(WordGroup::query(), $request->user())
            ->withCount('words')
            ->paginate(self::PER_PAGE);

        return WordGroupResource::collection($wordGroups);
    }

    public function groupCollections(Request $request): AnonymousResourceCollection
    {
        $groupCollections = $this->sharedWith(GroupCollection::query(), $request->user())
            ->withCount('wordGroups')
            ->paginate(self::PER_PAGE);

        return GroupCollectionResource::collection($groupCollections);
    }

    /**
     * Items the user is a member of and can see (a private item closed to contributions is hidden from its
     * members).
     *
     * @template TModel of WordGroup|GroupCollection
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function sharedWith(Builder $query, User $user): Builder
    {
        return $query
            ->whereHas('members', fn (Builder $query) => $query->whereKey($user->getKey()))
            ->visibleTo($user)
            ->with('creator')
            ->withIsFavorite($user)
            ->latest('updated_at')
            ->latest('id');
    }
}
