<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Determine whether the user can add a sentence to the group.
     *
     * @param User $user
     * @param Group $group
     * @return bool
     */
    public function addSentence(User $user, Group $group): bool
    {
        if ($user->isAdmin() || $group->user_id === $user->id) {
            return true;
        }

        return $group->visibility === Group::VISIBILITY_PUBLIC;
    }
}
