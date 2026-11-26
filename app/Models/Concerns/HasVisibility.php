<?php

namespace App\Models\Concerns;

use App\Enums\Visibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Shared behavior of the models that have a creator and a public/private visibility, and can be shared:
 * a private item is visible to its creator and, while it is open to contributions, to the users granted access
 * (members). The using model also uses AcceptsContributions and defines a `members()` belongs-to-many
 * relation to User. Being a member of a collection gives no access to the private groups it contains.
 *
 * @property Visibility $visibility
 * @property string $created_by
 */
trait HasVisibility
{
    /**
     * Per-instance cache of isSharedWith(), by user id, to avoid repeated queries in lists.
     *
     * @var array<string, bool>
     */
    private array $sharedWithCache = [];

    /**
     * Per-instance cache of isMember(), by user id.
     *
     * @var array<string, bool>
     */
    private array $memberCache = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    abstract public function members(): BelongsToMany;

    public function isCreatedBy(User $user): bool
    {
        return $this->created_by === $user->getKey();
    }

    public function isPrivate(): bool
    {
        return $this->visibility === Visibility::Private;
    }

    /**
     * Public items are visible to everyone, guests (null) included; private ones only to their creator and members.
     */
    public function isVisibleTo(?User $user): bool
    {
        if ($this->visibility === Visibility::Public) {
            return true;
        }
        if ($user === null) {
            return false;
        }
        if ($this->isCreatedBy($user)) {
            return true;
        }

        return $this->sharedWithCache[$user->getKey()] ??= $this->isSharedWith($user);
    }

    /**
     * Whether the creator accepted the user's access request (directly on this item, not through a
     * collection containing it).
     */
    public function isMember(User $user): bool
    {
        return $this->memberCache[$user->getKey()] ??= $this->members()->whereKey($user->getKey())->exists();
    }

    /**
     * Whether the user was granted access to this private item: they are a member and the item is still open
     * to contributions (a private item closed to contributions is only visible to its creator; reopening it
     * gives its members their access back).
     */
    protected function isSharedWith(User $user): bool
    {
        return $this->accepts_contributions && $this->isMember($user);
    }

    /**
     * Limits the query to the records the given user is allowed to see: only the public ones for a guest (null).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, ?User $user): void
    {
        if ($user === null) {
            $query->where($this->qualifyColumn('visibility'), Visibility::Public);

            return;
        }

        $query->where(function (Builder $query) use ($user): void {
            $query->where($this->qualifyColumn('visibility'), Visibility::Public)
                ->orWhere($this->qualifyColumn('created_by'), $user->getKey());
            $this->orWhereSharedWith($query, $user);
        });
    }

    /**
     * Adds the records shared with the user to a visibility query.
     *
     * @param  Builder<static>  $query
     */
    protected function orWhereSharedWith(Builder $query, User $user): void
    {
        $query->orWhere(fn (Builder $query) => $query
            ->where($this->qualifyColumn('accepts_contributions'), true)
            ->whereHas('members', fn (Builder $query) => $query->whereKey($user->getKey())));
    }

    /**
     * Casts the visibility column to the Visibility enum on every model using the trait.
     */
    protected function initializeHasVisibility(): void
    {
        $this->mergeCasts(['visibility' => Visibility::class]);
    }
}
