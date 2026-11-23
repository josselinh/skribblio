<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Exposes whether a user favorited the model, as an `is_favorite` attribute computed in the same query.
 * The using model defines a `favoritedBy()` belongs-to-many relation to User.
 */
trait Favoritable
{
    /**
     * Adds the `is_favorite` attribute for the given user to each record. Guests (null) have no favorites:
     * the attribute is left out, so that the SPA shows no favorite star.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withIsFavorite(Builder $query, ?User $user): void
    {
        if ($user === null) {
            return;
        }

        $query->withExists(['favoritedBy as is_favorite' => fn (Builder $query) => $query->whereKey($user->getKey())]);
    }

    /**
     * Loads the `is_favorite` attribute for the given user on an already retrieved model (nothing for a guest).
     */
    public function loadIsFavorite(?User $user): static
    {
        if ($user === null) {
            return $this;
        }

        return $this->loadExists(['favoritedBy as is_favorite' => fn (Builder $query) => $query->whereKey($user->getKey())]);
    }
}
