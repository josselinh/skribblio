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
     * The creator can always add words; the users who can see the group too, while it is open to contributions
     * (only its members when it is public and open to its members only).
     */
    public function addWord(User $user, WordGroup $wordGroup): Response
    {
        if (! $wordGroup->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        return $wordGroup->acceptsContributionFrom($user)
            ? Response::allow()
            : Response::deny($wordGroup->restrictsContributionsToMembers()
                ? 'Seuls les membres autorisés par le créateur peuvent contribuer à ce groupe.'
                : 'Ce groupe n’est pas ouvert aux contributions.');
    }

    /**
     * Favoriting requires being able to see the item. Removing a favorite is always allowed.
     */
    public function favorite(User $user, WordGroup $wordGroup): Response
    {
        return $this->view($user, $wordGroup);
    }

    /**
     * Anyone who can see a group can copy it into a group of their own. A member of a private group
     * can too: they can already export all its words.
     */
    public function duplicate(User $user, WordGroup $wordGroup): Response
    {
        return $this->view($user, $wordGroup);
    }

    /**
     * Only the creator can share a private item, or a public item open to its members only (get or
     * regenerate its access request link).
     */
    public function share(User $user, WordGroup $wordGroup): bool
    {
        return $wordGroup->isCreatedBy($user) && ($wordGroup->isPrivate() || $wordGroup->restrictsContributionsToMembers());
    }

    /**
     * Only the creator sees and decides the access requests, and revokes access.
     */
    public function manageAccess(User $user, WordGroup $wordGroup): bool
    {
        return $wordGroup->isCreatedBy($user);
    }
}
