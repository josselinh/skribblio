<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Word;

class WordPolicy
{
    /**
     * A word is visible to whoever can see its group.
     */
    public function view(User $user, Word $word): bool
    {
        return $word->wordGroup->isVisibleTo($user);
    }

    /**
     * The author of the word and the creator of its group can edit it,
     * as long as they can still see the group.
     */
    public function update(User $user, Word $word): bool
    {
        return $word->wordGroup->isVisibleTo($user)
            && ($word->isCreatedBy($user) || $word->wordGroup->isCreatedBy($user));
    }

    /**
     * The author of the word and the creator of its group can delete it.
     */
    public function delete(User $user, Word $word): bool
    {
        return $this->update($user, $word);
    }
}
