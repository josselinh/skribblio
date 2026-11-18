<?php

namespace App\Models;

use App\Models\Concerns\Favoritable;
use App\Models\Concerns\HasSearchableName;
use App\Models\Concerns\HasVisibility;
use App\Models\Concerns\Shareable;
use Database\Factories\GroupCollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named set of word groups. A collection must contain at least one group;
 * the groups it contains may have any visibility, including private groups
 * that other viewers of the collection cannot see.
 */
#[Fillable(['name', 'visibility'])]
class GroupCollection extends Model
{
    /** @use HasFactory<GroupCollectionFactory> */
    use Favoritable, HasFactory, HasSearchableName, HasUuids, HasVisibility, Shareable;

    /**
     * @return BelongsToMany<WordGroup, $this>
     */
    public function wordGroups(): BelongsToMany
    {
        return $this->belongsToMany(WordGroup::class, 'groups_collections')->withTimestamps();
    }

    /**
     * Users who added this collection to their favorites.
     *
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_group_collections')->withTimestamps();
    }

    /**
     * Users granted access to this private collection. They also see the private groups of the
     * collection's creator that it contains.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_collection_members')->withTimestamps();
    }

    /**
     * @return HasMany<GroupCollectionAccessRequest, $this>
     */
    public function accessRequests(): HasMany
    {
        return $this->hasMany(GroupCollectionAccessRequest::class);
    }
}
