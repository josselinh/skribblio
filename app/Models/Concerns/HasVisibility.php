<?php

namespace App\Models\Concerns;

use App\Enums\Visibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared behavior of the models that have a creator and a public/private visibility.
 *
 * @property Visibility $visibility
 * @property string $created_by
 */
trait HasVisibility
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCreatedBy(User $user): bool
    {
        return $this->created_by === $user->getKey();
    }

    public function isVisibleTo(User $user): bool
    {
        return $this->visibility === Visibility::Public || $this->isCreatedBy($user);
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
        });
    }

    /**
     * Casts the visibility column to the Visibility enum on every model using the trait.
     */
    protected function initializeHasVisibility(): void
    {
        $this->mergeCasts(['visibility' => Visibility::class]);
    }
}
