<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Auth\Access\Response;

class WordGroupPolicy
{
    /**
     * A group is visible to everyone when public, to its creator only when private.
     * Other users and guests (null) get a 404 so that they cannot tell whether a private group exists.
     */
    public function view(?User $user, WordGroup $wordGroup): Response
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
                ? __('word_groups.contributions_members_only')
                : __('word_groups.contributions_closed'));
    }

    /**
     * Whether the user changes or removes every contribution (every word), not only their own: the creator,
     * and members with a full sharing while the item is open to contributions.
     */
    public function manageContributions(User $user, WordGroup $wordGroup): bool
    {
        return $wordGroup->allowsChangingEveryContribution($user);
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
     * Only the creator can share the item while it is open to contributions, private or public open to its
     * members only (get or regenerate its access request link).
     */
    public function share(User $user, WordGroup $wordGroup): bool
    {
        return $wordGroup->isCreatedBy($user) && $wordGroup->isShareable();
    }

    /**
     * Only the creator sees and decides the access requests, and revokes access.
     */
    public function manageAccess(User $user, WordGroup $wordGroup): bool
    {
        return $wordGroup->isCreatedBy($user);
    }

    /**
     * A member (accepted through the share link) can leave the item, giving up the access it gave them. A
     * private item the user cannot see answers 404.
     */
    public function leave(User $user, WordGroup $wordGroup): Response
    {
        if (! $wordGroup->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        return $wordGroup->isMember($user) ? Response::allow() : Response::deny(__('word_groups.not_a_member'));
    }
}
