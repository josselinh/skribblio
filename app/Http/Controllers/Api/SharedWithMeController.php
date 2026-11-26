<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccessRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupCollectionResource;
use App\Http\Resources\WordGroupResource;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Private groups and collections of other users that the current user was granted access to, plus those
 * they requested from a share link and are still waiting for (`access_status: pending`, not accessible).
 */
class SharedWithMeController extends Controller
{
    /**
     * Number of items per page in listings.
     */
    private const int PER_PAGE = 12;

    public function wordGroups(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $wordGroups = $this->sharedOrRequested(WordGroup::query(), $user, fromShareLinkOnly: true)
            ->withCount('words')
            ->paginate(self::PER_PAGE);

        return WordGroupResource::collection($this->flagAccess($wordGroups, $user));
    }

    public function groupCollections(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $groupCollections = $this->sharedOrRequested(GroupCollection::query(), $user, fromShareLinkOnly: false)
            ->withCount('wordGroups')
            ->paginate(self::PER_PAGE);

        return GroupCollectionResource::collection($this->flagAccess($groupCollections, $user));
    }

    /**
     * Items the user is a member of and can see (a private item closed to contributions is hidden from its
     * members), or has a pending request on. Requests sent from a collection are left
     * out for groups: the user does not know those groups' names.
     *
     * @template TModel of WordGroup|GroupCollection
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function sharedOrRequested(Builder $query, User $user, bool $fromShareLinkOnly): Builder
    {
        $isUser = fn (Builder $query) => $query->whereKey($user->getKey());

        return $query
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->whereHas('members', $isUser)->visibleTo($user))
                ->orWhereHas('accessRequests', fn (Builder $query) => $query
                    ->where('user_id', $user->getKey())
                    ->where('status', AccessRequestStatus::Pending)
                    ->when($fromShareLinkOnly, fn (Builder $query) => $query->whereNull('via_group_collection_id'))))
            ->with('creator')
            ->withIsFavorite($user)
            ->latest('updated_at')
            ->latest('id');
    }

    /**
     * Sets `is_accessible` and `access_status` on each item of the page.
     *
     * @param  LengthAwarePaginator<int, WordGroup|GroupCollection>  $paginator
     * @return LengthAwarePaginator<int, WordGroup|GroupCollection>
     */
    private function flagAccess(LengthAwarePaginator $paginator, User $user): LengthAwarePaginator
    {
        $paginator->getCollection()->each(function (Model $item) use ($user): void {
            $isAccessible = $item->isVisibleTo($user);
            $item->forceFill([
                'is_accessible' => $isAccessible,
                'access_status' => $isAccessible ? 'granted' : 'pending',
            ]);
        });

        return $paginator;
    }
}
