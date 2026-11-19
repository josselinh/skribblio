<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListWordsRequest;
use App\Http\Requests\StoreWordRequest;
use App\Http\Requests\UpdateWordRequest;
use App\Http\Resources\WordResource;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WordController extends Controller
{
    /**
     * Number of words per page in listings.
     */
    private const int PER_PAGE = 20;

    /**
     * List the words of a group with their votes, most recently added first unless another order is asked.
     * `has_votes` tells whether any word of the group received a vote, so that the SPA shows the votes
     * of a group closed to contributions.
     */
    public function index(ListWordsRequest $request, WordGroup $wordGroup): AnonymousResourceCollection
    {
        $words = $wordGroup->words()
            ->with('creator')
            ->withVotes($request->user())
            ->orderBySort($request->sort(), $request->direction())
            ->paginate(self::PER_PAGE);
        $words->getCollection()->each->setRelation('wordGroup', $wordGroup);

        return WordResource::collection($words)->additional(['has_votes' => $wordGroup->hasWordVotes()]);
    }

    /**
     * Add several words to a group, written by the current user. Words already in the group
     * (as the database compares them: regardless of case and accents) are skipped, invalid
     * ones are reported; the others are added and the group is touched once.
     */
    public function store(StoreWordRequest $request, WordGroup $wordGroup): JsonResponse
    {
        $createdWords = new Collection;
        $skippedNames = [];

        DB::transaction(function () use ($request, $wordGroup, $createdWords, &$skippedNames): void {
            Word::withoutTouching(function () use ($request, $wordGroup, $createdWords, &$skippedNames): void {
                foreach ($request->validNames() as $name) {
                    try {
                        $createdWords->push(DB::transaction(function () use ($request, $wordGroup, $name): Word {
                            $word = $wordGroup->words()->make(['name' => $name]);
                            $word->creator()->associate($request->user());
                            $word->save();
                            $word->forceFill(['upvotes_count' => 0, 'downvotes_count' => 0, 'my_vote' => null]);

                            return $word->setRelation('wordGroup', $wordGroup);
                        }));
                    } catch (UniqueConstraintViolationException) {
                        $skippedNames[] = $name;
                    }
                }
            });

            if ($createdWords->isNotEmpty()) {
                $wordGroup->touch();
            }
        });

        return WordResource::collection($createdWords)
            ->additional(['skipped' => $skippedNames, 'invalid' => $request->invalidNames()])
            ->response()
            ->setStatusCode($createdWords->isNotEmpty() ? 201 : 200);
    }

    public function update(UpdateWordRequest $request, Word $word): WordResource
    {
        $word->update($request->validated());

        return new WordResource($word->load('creator')->loadVotes($request->user()));
    }

    public function destroy(Word $word): Response
    {
        Gate::authorize('delete', $word);

        $word->delete();

        return response()->noContent();
    }
}
