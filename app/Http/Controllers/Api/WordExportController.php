<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComposeExportRequest;
use App\Models\GroupCollection;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Words of a composition of groups and collections: the selected groups plus the groups of the
     * selected collections, without duplicates. Groups of those collections hidden from the user
     * are left out and only counted. The resolved group ids let the SPA save the composition as a
     * new collection.
     */
    public function composition(ComposeExportRequest $request): JsonResponse
    {
        $inSelectedGroupCollections = fn (Builder $query) => $query->whereKey($request->groupCollectionIds());

        $wordGroupIds = WordGroup::visibleTo($request->user())
            ->where(fn (Builder $query) => $query
                ->whereKey($request->wordGroupIds())
                ->orWhereHas('groupCollections', $inSelectedGroupCollections))
            ->orderBy('name')
            ->pluck('id');

        $hiddenWordGroupsCount = WordGroup::query()
            ->whereHas('groupCollections', $inSelectedGroupCollections)
            ->whereKeyNot($wordGroupIds)
            ->count();

        $names = Word::query()
            ->whereIn('word_group_id', $wordGroupIds)
            ->orderBy('name')
            ->pluck('name');

        return response()->json(['data' => [
            'words' => $names,
            'word_group_ids' => $wordGroupIds,
            'hidden_word_groups_count' => $hiddenWordGroupsCount,
        ]]);
    }
}
