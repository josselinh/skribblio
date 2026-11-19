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
     * The creator of the group can edit any word; the author of the word too, while the group is open to
     * contributions and they can still see it.
     */
    public function update(User $user, Word $word): bool
    {
        return $word->wordGroup->allowsChangingContributionOf($user, $word->created_by);
    }

    /**
     * The author of the word and the creator of its group can delete it.
     */
    public function delete(User $user, Word $word): bool
    {
        return $this->update($user, $word);
    }

    /**
     * While the group is open to contributions, whoever can see it votes for or against the words
     * (and changes or removes their vote), except the author of the word. Once the group is closed,
     * the votes are kept but nobody can vote any more.
     */
    public function vote(User $user, Word $word): bool
    {
        return $word->wordGroup->accepts_contributions
            && $word->wordGroup->isVisibleTo($user)
            && ! $word->isCreatedBy($user);
    }
}
