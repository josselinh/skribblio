<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

/**
 * Keep in sync with the visibility rules of the managers' applyFilters() (SQL side).
 */
class GroupPolicy
{
    /**
     * Admins can do everything: skip the other checks.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Determine whether the user can view the group and its sentences.
     */
    public function view(User $user, Group $group): bool
    {
        return in_array($group->visibility, [Group::VISIBILITY_PUBLIC, Group::VISIBILITY_PUBLIC_UNALTERABLE])
            || $group->user()->is($user);
    }

    /**
     * Determine whether the user can add sentences to the group.
     */
    public function addSentence(User $user, Group $group): bool
    {
        return $group->visibility == Group::VISIBILITY_PUBLIC
            || $group->user()->is($user);
    }
}
