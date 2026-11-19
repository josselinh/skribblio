<?php

namespace App\Models\Concerns;

use App\Models\User;

/**
 * The "open to contributions" option of a group (others add words) or a collection (others add groups),
 * used with HasVisibility. Its creator always manages the content. When the option is on, the users who
 * can see the item add to it and manage their own contributions; when it is off, their contributions are
 * kept but only the creator can change them.
 *
 * @property bool $accepts_contributions
 */
trait AcceptsContributions
{
    /**
     * Whether the user can add content (words or groups) to the item.
     */
    public function acceptsContributionFrom(User $user): bool
    {
        return $this->isCreatedBy($user) || ($this->accepts_contributions && $this->isVisibleTo($user));
    }

    /**
     * Whether the user can change or remove a contribution made by the given user (null when that user
     * was deleted): the creator always can, a contributor only their own, while the item is open.
     */
    public function allowsChangingContributionOf(User $user, ?string $contributorId): bool
    {
        return $this->isCreatedBy($user)
            || ($this->accepts_contributions && $contributorId === $user->getKey() && $this->isVisibleTo($user));
    }

    protected function initializeAcceptsContributions(): void
    {
        $this->mergeCasts(['accepts_contributions' => 'boolean']);
    }
}
