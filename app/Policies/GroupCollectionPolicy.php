<?php

namespace App\Policies;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Auth\Access\Response;

class GroupCollectionPolicy
{
    /**
     * A collection is visible to everyone when public, to its creator only when private.
     * Other users get a 404 so that they cannot tell whether a private collection exists.
     */
    public function view(User $user, GroupCollection $groupCollection): Response
    {
        return $groupCollection->isVisibleTo($user) ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, GroupCollection $groupCollection): bool
    {
        return $groupCollection->isCreatedBy($user);
    }

    public function delete(User $user, GroupCollection $groupCollection): bool
    {
        return $groupCollection->isCreatedBy($user);
    }

    /**
     * The creator of the collection can add any group they can see, including
     * public groups created by other users.
     */
    public function attachWordGroup(User $user, GroupCollection $groupCollection, WordGroup $wordGroup): bool
    {
        return $groupCollection->isCreatedBy($user) && $wordGroup->isVisibleTo($user);
    }

    public function favorite(User $user, GroupCollection $groupCollection): bool
    {
        return $groupCollection->isVisibleTo($user);
    }
}
