<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWordGroupRequest;
use App\Http\Requests\UpdateWordGroupRequest;
use App\Http\Resources\WordGroupResource;
use App\Models\WordGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class WordGroupController extends Controller
{
    /**
     * Number of groups per page in listings.
     */
    private const int PER_PAGE = 12;

    /**
     * List the groups created by the current user, most recently updated first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $wordGroups = $request->user()
            ->wordGroups()
            ->withIsFavorite($request->user())
            ->latest('updated_at')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return WordGroupResource::collection($wordGroups);
    }

    /**
     * Create a group owned by the current user.
     */
    public function store(StoreWordGroupRequest $request): JsonResponse
    {
        $wordGroup = $request->user()->wordGroups()->create($request->validated());
        $wordGroup->setAttribute('is_favorite', false);

        return (new WordGroupResource($wordGroup))->response()->setStatusCode(201);
    }

    public function show(Request $request, WordGroup $wordGroup): WordGroupResource
    {
        Gate::authorize('view', $wordGroup);

        return new WordGroupResource($wordGroup->load('creator')->loadIsFavorite($request->user()));
    }

    public function update(UpdateWordGroupRequest $request, WordGroup $wordGroup): WordGroupResource
    {
        $wordGroup->update($request->validated());

        return new WordGroupResource($wordGroup->load('creator')->loadIsFavorite($request->user()));
    }

    /**
     * Delete a group with its words; it is removed from the collections containing it, which may leave a collection empty.
     * Users who cannot see the item get a 404, the others a 403.
     */
    public function destroy(WordGroup $wordGroup): Response
    {
        Gate::authorize('view', $wordGroup);
        Gate::authorize('delete', $wordGroup);

        $wordGroup->delete();

        return response()->noContent();
    }
}
