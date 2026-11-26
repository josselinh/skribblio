<?php

namespace App\Models\Concerns;

use App\Enums\Visibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * The "open to contributions" option of a group (others add words) or a collection (others add groups),
 * used with HasVisibility and Shareable. Its creator always manages the content. When the option is on,
 * the users who can see the item add to it and manage their own contributions; when it is off, their
 * contributions are kept but only the creator can change them.
 *
 * On a public item, `contributions_members_only` restricts the contributions to its members: the users
 * the creator accepted through the share link. It has no effect on a private item, whose viewers are
 * already the users granted access.
 *
 * @property bool $accepts_contributions
 * @property bool $contributions_members_only
 */
trait AcceptsContributions
{
    /**
     * Whether the item is public, open to contributions, but only from its members.
     */
    public function restrictsContributionsToMembers(): bool
    {
        return $this->accepts_contributions && $this->contributions_members_only && ! $this->isPrivate();
    }

    /**
     * Whether the user can add content (words or groups) to the item.
     */
    public function acceptsContributionFrom(User $user): bool
    {
        return $this->isCreatedBy($user) || $this->isContributor($user);
    }

    /**
     * Whether the user can change or remove every contribution, whoever made it: the creator always can, a
     * member with a full sharing (see Shareable::sharesFullyWith()) while the item is open to them.
     */
    public function allowsChangingEveryContribution(User $user): bool
    {
        return $this->isCreatedBy($user)
            || ($this->isContributor($user) && $this->sharesFullyWith($user));
    }

    /**
     * Whether the user can change or remove a contribution made by the given user (null when that user
     * was deleted): the creator always can, a member with a full sharing any of them and another contributor
     * only their own, while the item is open to them.
     */
    public function allowsChangingContributionOf(User $user, ?string $contributorId): bool
    {
        return $this->allowsChangingEveryContribution($user)
            || ($contributorId === $user->getKey() && $this->isContributor($user));
    }

    /**
     * Whether the item is open to contributions from the user, who is not necessarily its creator.
     */
    private function isContributor(User $user): bool
    {
        return $this->accepts_contributions
            && $this->isVisibleTo($user)
            && (! $this->restrictsContributionsToMembers() || $this->isMember($user));
    }

    /**
     * Limits the query to the items the user can add content to, as `acceptsContributionFrom()` decides: their own
     * items, and the items they can see that are open to contributions (and, when public and open to its members
     * only, of which they are a member).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function acceptingContributionsFrom(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->where($this->qualifyColumn('created_by'), $user->getKey())
                ->orWhere(fn (Builder $query) => $query
                    ->where($this->qualifyColumn('accepts_contributions'), true)
                    ->visibleTo($user)
                    ->where(fn (Builder $query) => $query
                        ->where($this->qualifyColumn('visibility'), Visibility::Private)
                        ->orWhere($this->qualifyColumn('contributions_members_only'), false)
                        ->orWhereHas('members', fn (Builder $query) => $query->whereKey($user->getKey()))));
        });
    }

    protected function initializeAcceptsContributions(): void
    {
        $this->mergeCasts([
            'accepts_contributions' => 'boolean',
            'contributions_members_only' => 'boolean',
        ]);
    }
}
