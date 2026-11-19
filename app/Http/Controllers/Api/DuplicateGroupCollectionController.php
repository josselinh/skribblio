<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupCollectionResource;
use App\Models\GroupCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DuplicateGroupCollectionController extends Controller
{
    /**
     * Copy a collection the user can see, with copies of the groups they can see, into a collection
     * of their own (see `GroupCollection::duplicateFor()`). The response tells how many hidden groups
     * were not copied (`hidden_word_groups_count`), never which ones. Refused when no group is visible,
     * since a collection must contain at least one group.
     */
    public function __invoke(Request $request, GroupCollection $groupCollection): JsonResponse
    {
        Gate::authorize('duplicate', $groupCollection);
        $user = $request->user();

        $visibleWordGroupsCount = $groupCollection->wordGroups()->visibleTo($user)->count();
        if ($visibleWordGroupsCount === 0) {
            throw ValidationException::withMessages([
                'word_groups' => 'Aucun groupe de cette collection ne vous est visible : il n’y a rien à copier.',
            ]);
        }
        $hiddenWordGroupsCount = $groupCollection->wordGroups()->count() - $visibleWordGroupsCount;

        $copy = $groupCollection->duplicateFor($user);
        $copy->setAttribute('is_favorite', false);
        $copy->setAttribute('hidden_word_groups_count', $hiddenWordGroupsCount);

        return (new GroupCollectionResource($copy))->response()->setStatusCode(201);
    }
}
