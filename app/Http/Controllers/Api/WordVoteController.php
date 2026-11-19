<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoteWordRequest;
use App\Http\Resources\WordResource;
use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The current user's vote for or against a word. Both actions are idempotent and return the word
 * with its updated counts.
 */
class WordVoteController extends Controller
{
    /**
     * Record the vote, replacing the previous one.
     */
    public function update(VoteWordRequest $request, Word $word): WordResource
    {
        $word->voteFor($request->user(), $request->vote());

        return $this->wordResource($request, $word);
    }

    /**
     * Remove the vote, if any. Users who cannot see the group get a 404.
     */
    public function destroy(Request $request, Word $word): WordResource
    {
        Gate::authorize('view', $word->wordGroup);
        Gate::authorize('vote', $word);

        $word->removeVoteOf($request->user());

        return $this->wordResource($request, $word);
    }

    private function wordResource(Request $request, Word $word): WordResource
    {
        return new WordResource($word->load('creator')->loadVotes($request->user()));
    }
}
