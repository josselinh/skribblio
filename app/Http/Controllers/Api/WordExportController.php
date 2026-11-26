<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupCollection;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Every word of a group or a collection, unpaginated, with its score (upvotes minus downvotes), for the
 * Skribbl.io export. The SPA leaves out the words scored too low, deduplicates, shuffles and formats the
 * list (and, for a collection, combines the chosen groups).
 */
class WordExportController extends Controller
{
    public function wordGroup(WordGroup $wordGroup): JsonResponse
    {
        Gate::authorize('view', $wordGroup);

        $words = $wordGroup->words()->select('name')->withScore()->orderBy('name')->get();

        return response()->json(['data' => self::exportedWords($words)]);
    }

    /**
     * The groups of the collection that the current user can see, sorted by name, each with its words
     * sorted by name (nothing tells that other groups are hidden from them). The SPA lets the user
     * pick which groups to export, and adds them to the export composer.
     */
    public function groupCollection(Request $request, GroupCollection $groupCollection): JsonResponse
    {
        Gate::authorize('view', $groupCollection);

        $wordGroups = $groupCollection->wordGroups()
            ->visibleTo($request->user())
            ->with(['words' => fn (HasMany $query) => $query->select(['id', 'word_group_id', 'name'])->withScore()->orderBy('name')])
            ->orderBy('name')
            ->get(['word_groups.id', 'word_groups.name']);

        return response()->json(['data' => [
            'word_groups' => $wordGroups->map(fn (WordGroup $wordGroup): array => [
                'id' => $wordGroup->id,
                'name' => $wordGroup->name,
                'words' => self::exportedWords($wordGroup->words),
            ]),
        ]]);
    }

    /**
     * @param  EloquentCollection<int, Word>  $words  loaded with the `withScore` scope
     * @return list<array{name: string, score: int}>
     */
    private static function exportedWords(EloquentCollection $words): array
    {
        return $words->map(fn (Word $word): array => ['name' => $word->name, 'score' => (int) $word->score])->values()->all();
    }
}
