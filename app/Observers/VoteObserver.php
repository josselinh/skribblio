<?php

namespace App\Observers;

use App\Models\Sentence;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;

class VoteObserver
{
    /**
     * Handle the vote "saved" event.
     *
     * @param Vote $vote
     * @return void
     */
    public function saved(Vote $vote)
    {
        $note = 0;

        // No existing vote
        if (!$vote->getOriginal('note')) {
            $note = $vote->getAttribute('note');
        }

        // Updating vote
        if ($vote->getOriginal('note') && $vote->getOriginal('note') !== $vote->getAttribute('note')) {
            $note = $vote->getAttribute('note') * 2;
        }

        Sentence::where('id', $vote->getAttribute('sentence_id'))->update(['note' => DB::raw('note + ' . $note)]);
    }
}
