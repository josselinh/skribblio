<?php

namespace App\Models;

use App\Models\Concerns\Favoritable;
use App\Models\Concerns\HasSearchableName;
use App\Models\Concerns\HasVisibility;
use Database\Factories\WordGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'visibility'])]
class WordGroup extends Model
{
    /** @use HasFactory<WordGroupFactory> */
    use Favoritable, HasFactory, HasSearchableName, HasUuids, HasVisibility;

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
}
