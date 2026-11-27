<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupCollectionRequest;
use App\Http\Requests\UpdateGroupCollectionRequest;
use App\Http\Resources\GroupCollectionResource;
use App\Models\GroupCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
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
            ->with('creator')
            ->withCount('wordGroups')
            ->withIsFavorite($request->user())
            ->latest('updated_at')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return GroupCollectionResource::collection($groupCollections);
    }

    /**
     * Create an empty collection owned by the current user, in their content language unless another one is
     * given; its groups are added afterwards.
     */
    public function store(StoreGroupCollectionRequest $request): JsonResponse
    {
        $groupCollection = $request->user()->groupCollections()->create([
            'language' => $request->user()->content_locale,
            ...$request->validated(),
        ]);
        $groupCollection->setAttribute('is_favorite', false);

        return (new GroupCollectionResource($groupCollection))->response()->setStatusCode(201);
    }

    /**
     * Show a collection with the groups the current user can see (each with who added it). Nothing tells that
     * other groups are hidden from them: they cannot learn that the collection contains private groups.
     */
    public function show(Request $request, GroupCollection $groupCollection): GroupCollectionResource
    {
        Gate::authorize('view', $groupCollection);

        $visibleWordGroups = $groupCollection->wordGroups()
            ->visibleTo($request->user())
            ->with('creator')
            ->withCount('words')
            ->withIsFavorite($request->user())
            ->orderBy('name')
            ->get();
        EloquentCollection::make($visibleWordGroups->pluck('pivot'))->load('addedBy');

        $groupCollection->setRelation('wordGroups', $visibleWordGroups);

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
