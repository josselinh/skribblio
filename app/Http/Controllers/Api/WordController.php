<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     * List the words of a group, most recently added first.
     */
    public function index(WordGroup $wordGroup): AnonymousResourceCollection
    {
        Gate::authorize('view', $wordGroup);

        $words = $wordGroup->words()
            ->with('creator')
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE);
        $words->getCollection()->each->setRelation('wordGroup', $wordGroup);

        return WordResource::collection($words);
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

        return new WordResource($word->load('creator'));
    }

    public function destroy(Word $word): Response
    {
        Gate::authorize('delete', $word);

        $word->delete();

        return response()->noContent();
    }
}
