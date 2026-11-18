<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachWordGroupRequest;
use App\Models\GroupCollection;
use App\Models\WordGroup;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Adds groups to and removes groups from a collection. Both actions touch the collection's `updated_at`.
 */
class GroupCollectionWordGroupController extends Controller
{
    /**
     * Add a group to the collection. Adding a group already in it does nothing.
     */
    public function store(AttachWordGroupRequest $request, GroupCollection $groupCollection): Response
    {
        $groupCollection->wordGroups()->syncWithoutDetaching([$request->wordGroup()->getKey()]);
        $groupCollection->touch();

        return response()->noContent();
    }

    /**
     * Remove a group from the collection, which must keep at least one group.
     *
     * @throws ValidationException
     */
    public function destroy(GroupCollection $groupCollection, WordGroup $wordGroup): Response
    {
        Gate::authorize('update', $groupCollection);
        abort_unless($groupCollection->wordGroups()->whereKey($wordGroup->getKey())->exists(), 404);

        if ($groupCollection->wordGroups()->count() === 1) {
            throw ValidationException::withMessages([
                'word_group_id' => 'Une collection doit contenir au moins un groupe.',
            ]);
        }

        $groupCollection->wordGroups()->detach($wordGroup);
        $groupCollection->touch();

        return response()->noContent();
    }
}
