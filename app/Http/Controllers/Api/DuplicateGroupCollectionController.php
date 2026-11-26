<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DuplicateGroupCollectionRequest;
use App\Http\Resources\GroupCollectionResource;
use App\Models\GroupCollection;
use Illuminate\Http\JsonResponse;

class DuplicateGroupCollectionController extends Controller
{
    /**
     * Copy a collection the user can see into a collection of their own, with copies of the groups
     * they can see or, when `copy_word_groups` is false, with those groups themselves (see
     * `GroupCollection::duplicateFor()`). The groups hidden from them are left out without a word. When no
     * group is visible, the copy is empty.
     */
    public function __invoke(DuplicateGroupCollectionRequest $request, GroupCollection $groupCollection): JsonResponse
    {
        $user = $request->user();

        $copy = $groupCollection->duplicateFor($user, copyWordGroups: $request->copiesWordGroups());
        $copy->setAttribute('is_favorite', false);

        return (new GroupCollectionResource($copy))->response()->setStatusCode(201);
    }
}
