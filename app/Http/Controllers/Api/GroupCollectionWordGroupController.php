<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachWordGroupRequest;
use App\Http\Requests\DetachWordGroupsRequest;
use App\Models\GroupCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Adds groups to and removes groups from a collection. Both actions touch the collection's `updated_at`.
 */
class GroupCollectionWordGroupController extends Controller
{
    /**
     * Add a group to the collection, recording who added it. Adding a group already in it does nothing
     * (and keeps who added it first).
     */
    public function store(AttachWordGroupRequest $request, GroupCollection $groupCollection): Response
    {
        $wordGroup = $request->wordGroup();
        if (! $groupCollection->wordGroups()->whereKey($wordGroup->getKey())->exists()) {
            $groupCollection->wordGroups()->attach($wordGroup, ['added_by' => $request->user()->getKey()]);
            $groupCollection->touch();
        }

        return response()->noContent();
    }

    /**
     * Remove several groups from the collection, which must keep at least one group. The creator can remove
     * any group, a contributor the groups they added while the collection is open to contributions. Nothing
     * is removed unless the user may remove every one of them; the collection is touched once.
     */
    public function destroy(DetachWordGroupsRequest $request, GroupCollection $groupCollection): Response
    {
        $wordGroups = $groupCollection->wordGroups()->whereKey($request->wordGroupIds())->get();
        foreach ($wordGroups as $wordGroup) {
            Gate::authorize('detachWordGroup', [$groupCollection, $wordGroup]);
        }

        DB::transaction(function () use ($groupCollection, $wordGroups): void {
            $groupCollection->wordGroups()->detach($wordGroups->modelKeys());
            $groupCollection->touch();
        });

        return response()->noContent();
    }
}
