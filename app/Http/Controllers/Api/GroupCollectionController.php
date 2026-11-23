<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccessRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupCollectionRequest;
use App\Http\Requests\UpdateGroupCollectionRequest;
use App\Http\Resources\GroupCollectionResource;
use App\Models\GroupCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GroupCollectionController extends Controller
{
    /**
     * Number of collections per page in listings.
     */
    private const int PER_PAGE = 12;

    /**
     * List the collections created by the current user, most recently updated first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $groupCollections = $request->user()
            ->groupCollections()
            ->withIsFavorite($request->user())
            ->latest('updated_at')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return GroupCollectionResource::collection($groupCollections);
    }

    /**
     * Create a collection owned by the current user, with its groups (all in its language).
     */
    public function store(StoreGroupCollectionRequest $request): JsonResponse
    {
        $groupCollection = DB::transaction(function () use ($request) {
            $groupCollection = $request->user()->groupCollections()->create([
                ...$request->safe()->only(['name', 'visibility', 'accepts_contributions', 'contributions_members_only']),
                'language' => $request->language(),
            ]);
            $groupCollection->wordGroups()->attach($request->wordGroups(), ['added_by' => $request->user()->getKey()]);

            return $groupCollection->setAttribute('is_favorite', false);
        });

        return (new GroupCollectionResource($groupCollection))->response()->setStatusCode(201);
    }

    /**
     * Show a collection with the groups the current user can see, and how many others are hidden from them
     * (private groups of other users, or every private group for a guest). Guests cannot request access, so
     * none of the hidden groups are requested by them.
     */
    public function show(Request $request, GroupCollection $groupCollection): GroupCollectionResource
    {
        Gate::authorize('view', $groupCollection);

        $visibleWordGroups = $groupCollection->wordGroups()
            ->visibleTo($request->user())
            ->with('creator')
            ->withIsFavorite($request->user())
            ->orderBy('name')
            ->get();

        $groupCollection->setRelation('wordGroups', $visibleWordGroups);
        $hiddenWordGroups = $groupCollection->wordGroups()->whereNotIn('word_groups.id', $visibleWordGroups->modelKeys());
        $groupCollection->setAttribute('hidden_word_groups_count', $hiddenWordGroups->count());
        $groupCollection->setAttribute(
            'hidden_word_groups_requested_count',
            $request->user() === null ? 0 : $hiddenWordGroups->whereHas('accessRequests', fn (Builder $query) => $query
                ->where('user_id', $request->user()->getKey())
                ->where('status', AccessRequestStatus::Pending))->count(),
        );

        return new GroupCollectionResource($groupCollection->load('creator')->loadIsFavorite($request->user()));
    }

    public function update(UpdateGroupCollectionRequest $request, GroupCollection $groupCollection): GroupCollectionResource
    {
        $groupCollection->update($request->validated());

        return new GroupCollectionResource($groupCollection->load('creator')->loadIsFavorite($request->user()));
    }

    /**
     * Delete a collection; its groups are kept.
     * Users who cannot see the item get a 404, the others a 403.
     */
    public function destroy(GroupCollection $groupCollection): Response
    {
        Gate::authorize('view', $groupCollection);
        Gate::authorize('delete', $groupCollection);

        $groupCollection->delete();

        return response()->noContent();
    }
}
