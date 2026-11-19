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

    /**
     * Anyone who can see a collection can copy it, with the groups they can see, into a collection of their own.
     */
    public function duplicate(User $user, GroupCollection $groupCollection): Response
    {
        return $this->view($user, $groupCollection);
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
     * The creator can always add groups; the users who can see the collection too, while it is open to
     * contributions (only its members when it is public and open to its members only).
     */
    public function addWordGroup(User $user, GroupCollection $groupCollection): Response
    {
        if (! $groupCollection->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        return $groupCollection->acceptsContributionFrom($user)
            ? Response::allow()
            : Response::deny($groupCollection->restrictsContributionsToMembers()
                ? 'Seuls les membres autorisés par le créateur peuvent contribuer à cette collection.'
                : 'Cette collection n’est pas ouverte aux contributions.');
    }

    /**
     * A user who can add groups to the collection can add any group they can see, including public groups
     * of other users and their own private groups (hidden from the other viewers, who can request access).
     */
    public function attachWordGroup(User $user, GroupCollection $groupCollection, WordGroup $wordGroup): bool
    {
        return $groupCollection->acceptsContributionFrom($user) && $wordGroup->isVisibleTo($user);
    }

    /**
     * The creator can remove any group; a contributor only the groups they added, while the collection
     * is open to contributions. Uses the group's pivot when it was loaded through the collection.
     */
    public function detachWordGroup(User $user, GroupCollection $groupCollection, WordGroup $wordGroup): bool
    {
        if ($groupCollection->isCreatedBy($user)) {
            return true;
        }

        $pivot = $wordGroup->relationLoaded('pivot') && $wordGroup->pivot->group_collection_id === $groupCollection->getKey()
            ? $wordGroup->pivot
            : $groupCollection->wordGroups()->whereKey($wordGroup->getKey())->first()?->pivot;

        return $pivot !== null && $groupCollection->allowsChangingContributionOf($user, $pivot->added_by);
    }

    /**
     * Favoriting requires being able to see the item. Removing a favorite is always allowed.
     */
    public function favorite(User $user, GroupCollection $groupCollection): Response
    {
        return $this->view($user, $groupCollection);
    }

    /**
     * Only the creator can share a private item, or a public item open to its members only (get or
     * regenerate its access request link).
     */
    public function share(User $user, GroupCollection $groupCollection): bool
    {
        return $groupCollection->isCreatedBy($user) && ($groupCollection->isPrivate() || $groupCollection->restrictsContributionsToMembers());
    }

    /**
     * Only the creator sees and decides the access requests, and revokes access.
     */
    public function manageAccess(User $user, GroupCollection $groupCollection): bool
    {
        return $groupCollection->isCreatedBy($user);
    }
}
