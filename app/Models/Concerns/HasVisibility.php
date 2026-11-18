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
 * a private item is visible to its creator and to the users granted access (members). The using model
 * defines a `members()` belongs-to-many relation to User, and may widen the access rules by overriding
 * `orWhereSharedWith()` and `isSharedWith()`.
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

    public function isVisibleTo(User $user): bool
    {
        if ($this->visibility === Visibility::Public || $this->isCreatedBy($user)) {
            return true;
        }

        return $this->sharedWithCache[$user->getKey()] ??= $this->isSharedWith($user);
    }

    /**
     * Whether the user was granted access to this private item.
     */
    protected function isSharedWith(User $user): bool
    {
        return $this->members()->whereKey($user->getKey())->exists();
    }

    /**
     * Limits the query to the records the given user is allowed to see.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
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
        $query->orWhereHas('members', fn (Builder $query) => $query->whereKey($user->getKey()));
    }

    /**
     * Casts the visibility column to the Visibility enum on every model using the trait.
     */
    protected function initializeHasVisibility(): void
    {
        $this->mergeCasts(['visibility' => Visibility::class]);
    }
}
