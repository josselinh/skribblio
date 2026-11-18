<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupCollection;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Every word of a group or a collection, unpaginated, for the Skribbl.io export. The SPA
 * deduplicates, shuffles and formats the list.
 */
class WordExportController extends Controller
{
    public function wordGroup(WordGroup $wordGroup): JsonResponse
    {
        Gate::authorize('view', $wordGroup);

        return response()->json(['data' => $wordGroup->words()->orderBy('name')->pluck('name')]);
    }

    /**
     * Words of the groups of the collection that the current user can see; hidden groups are left out.
     */
    public function groupCollection(Request $request, GroupCollection $groupCollection): JsonResponse
    {
        Gate::authorize('view', $groupCollection);

        $visibleWordGroupIds = $groupCollection->wordGroups()
            ->visibleTo($request->user())
            ->select('word_groups.id');

        $names = Word::query()
            ->whereIn('word_group_id', $visibleWordGroupIds)
            ->orderBy('name')
            ->pluck('name');

        return response()->json(['data' => $names]);
    }
}
