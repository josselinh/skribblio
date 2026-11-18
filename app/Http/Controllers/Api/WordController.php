<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWordRequest;
use App\Http\Requests\UpdateWordRequest;
use App\Http\Resources\WordResource;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
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
     * Add a word to a group, written by the current user.
     */
    public function store(StoreWordRequest $request, WordGroup $wordGroup): JsonResponse
    {
        $word = $wordGroup->words()->make($request->validated());
        $word->creator()->associate($request->user());
        $word->save();

        return (new WordResource($word->setRelation('wordGroup', $wordGroup)))->response()->setStatusCode(201);
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
