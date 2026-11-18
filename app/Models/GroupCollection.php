<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use Database\Factories\GroupCollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A named set of word groups. A collection must contain at least one group;
 * the groups it contains may have any visibility, including private groups
 * that other viewers of the collection cannot see.
 */
#[Fillable(['name', 'visibility'])]
class GroupCollection extends Model
{
    /** @use HasFactory<GroupCollectionFactory> */
    use HasFactory, HasUuids, HasVisibility;

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
}
