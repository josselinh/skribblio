<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Auth\Access\Response;

class WordGroupPolicy
{
    /**
     * A group is visible to everyone when public, to its creator only when private.
     * Other users get a 404 so that they cannot tell whether a private group exists.
     */
    public function view(User $user, WordGroup $wordGroup): Response
    {
        return $wordGroup->isVisibleTo($user) ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WordGroup $wordGroup): bool
    {
        return $wordGroup->isCreatedBy($user);
    }

    public function delete(User $user, WordGroup $wordGroup): bool
    {
        return $wordGroup->isCreatedBy($user);
    }

    /**
     * Anyone can add words to a public group; only the creator to a private one.
     */
    public function addWord(User $user, WordGroup $wordGroup): Response
    {
        return $this->view($user, $wordGroup);
    }

    /**
     * Favoriting requires being able to see the item. Removing a favorite is always allowed.
     */
    public function favorite(User $user, WordGroup $wordGroup): Response
    {
        return $this->view($user, $wordGroup);
    }
}
