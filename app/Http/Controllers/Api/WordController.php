<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyWordsRequest;
use App\Http\Requests\ListWordsRequest;
use App\Http\Requests\StoreWordRequest;
use App\Http\Requests\UpdateWordRequest;
use App\Http\Requests\UpdateWordsRequest;
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
use Illuminate\Validation\ValidationException;

class WordController extends Controller
{
    /**
     * Number of words per page in listings.
     */
    private const int PER_PAGE = 20;

    /**
     * List the words of a group with their votes, most recently added first unless another order is asked.
     */
    public function index(ListWordsRequest $request, WordGroup $wordGroup): AnonymousResourceCollection
    {
        $words = $wordGroup->words()
            ->with('creator')
            ->withVotes($request->user())
            ->orderBySort($request->sort(), $request->direction())
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

    /**
     * Rename several words of a group at once. Nothing is renamed unless the user may update every
     * one of them; the group is touched once. A conflict that validation could not foresee (two names
     * that the database considers equal, such as with and without accents) is reported on the word.
     */
    public function updateMany(UpdateWordsRequest $request, WordGroup $wordGroup): Response
    {
        $namesByWordId = $request->namesByWordId();
        $words = $wordGroup->words()->whereKey(array_keys($namesByWordId))->get();
        $words->each->setRelation('wordGroup', $wordGroup);
        foreach ($words as $word) {
            Gate::authorize('update', $word);
        }

        DB::transaction(function () use ($wordGroup, $words, $namesByWordId, $request): void {
            Word::withoutTouching(function () use ($words, $namesByWordId, $request): void {
                foreach ($words as $word) {
                    try {
                        $word->update(['name' => $namesByWordId[$word->id]]);
                    } catch (UniqueConstraintViolationException) {
                        $index = array_search($word->id, array_column($request->validated('words'), 'id'), true);

                        throw ValidationException::withMessages([
                            "words.{$index}.name" => __('words.name_taken'),
                        ]);
                    }
                }
            });
            $wordGroup->touch();
        });

        return response()->noContent();
    }

    public function destroy(Word $word): Response
    {
        Gate::authorize('delete', $word);

        $word->delete();

        return response()->noContent();
    }

    /**
     * Delete several words of a group at once. Nothing is deleted unless the user may delete
     * every one of them; the group is touched once.
     */
    public function destroyMany(DestroyWordsRequest $request, WordGroup $wordGroup): Response
    {
        $words = $wordGroup->words()->whereKey($request->wordIds())->get();
        $words->each->setRelation('wordGroup', $wordGroup);
        foreach ($words as $word) {
            Gate::authorize('delete', $word);
        }

        DB::transaction(function () use ($wordGroup, $words): void {
            $wordGroup->words()->whereKey($words->modelKeys())->delete();
            $wordGroup->touch();
        });

        return response()->noContent();
    }
}
