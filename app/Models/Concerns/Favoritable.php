<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Notifications\ItemFavorited;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Exposes whether a user favorited the model, as an `is_favorite` attribute computed in the same query.
 * The using model defines a `favoritedBy()` belongs-to-many relation to User and uses HasVisibility.
 */
trait Favoritable
{
    /**
     * How long, in seconds, the creator is not notified again when the same user favorites the item again
     * (after removing it), so that toggling the star does not flood their inbox.
     */
    private const int FAVORITED_NOTIFICATION_COOLDOWN = 86400;

    /**
     * Adds the item to the user's favorites (adding it again does nothing) and tells its creator, unless the
     * user is the creator.
     */
    public function addToFavoritesOf(User $user): void
    {
        $changes = $this->favoritedBy()->syncWithoutDetaching([$user->getKey()]);

        if ($changes['attached'] === [] || $this->isCreatedBy($user)) {
            return;
        }

        $cooldownKey = 'favorited-notification:'.$this->getMorphClass().":{$this->getKey()}:{$user->getKey()}";
        if (Cache::add($cooldownKey, true, self::FAVORITED_NOTIFICATION_COOLDOWN)) {
            $this->creator->notify(new ItemFavorited($this, $user));
        }
    }

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
