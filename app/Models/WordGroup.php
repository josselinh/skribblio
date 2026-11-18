<?php

namespace App\Models;

use App\Models\Concerns\Favoritable;
use App\Models\Concerns\HasSearchableName;
use App\Models\Concerns\HasVisibility;
use App\Models\Concerns\Shareable;
use Database\Factories\WordGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'visibility'])]
class WordGroup extends Model
{
    /** @use HasFactory<WordGroupFactory> */
    use Favoritable, HasFactory, HasSearchableName, HasUuids, HasVisibility, Shareable;

    /**
     * @return HasMany<Word, $this>
     */
    public function words(): HasMany
    {
        return $this->hasMany(Word::class);
    }

    /**
     * @return BelongsToMany<GroupCollection, $this>
     */
    public function groupCollections(): BelongsToMany
    {
        return $this->belongsToMany(GroupCollection::class, 'groups_collections')->withTimestamps();
    }

    /**
     * Users who added this group to their favorites.
     *
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_word_groups')->withTimestamps();
    }

    /**
     * Users granted access to this private group.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'word_group_members')->withTimestamps();
    }

    /**
     * @return HasMany<WordGroupAccessRequest, $this>
     */
    public function accessRequests(): HasMany
    {
        return $this->hasMany(WordGroupAccessRequest::class);
    }

    /**
     * A private group is also shared with the members of a collection containing it, when that
     * collection belongs to the group's creator: sharing a collection never exposes other people's groups.
     */
    protected function isSharedWith(User $user): bool
    {
        return $this->members()->whereKey($user->getKey())->exists()
            || $this->groupCollections()
                ->where('group_collections.created_by', $this->created_by)
                ->whereHas('members', fn (Builder $query) => $query->whereKey($user->getKey()))
                ->exists();
    }

    /**
     * @param  Builder<static>  $query
     */
    protected function orWhereSharedWith(Builder $query, User $user): void
    {
        $query->orWhereHas('members', fn (Builder $query) => $query->whereKey($user->getKey()))
            ->orWhereHas('groupCollections', fn (Builder $query) => $query
                ->whereColumn('group_collections.created_by', 'word_groups.created_by')
                ->whereHas('members', fn (Builder $query) => $query->whereKey($user->getKey())));
    }
}
