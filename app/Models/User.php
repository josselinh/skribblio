<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Word groups created by the user.
     *
     * @return HasMany<WordGroup, $this>
     */
    public function wordGroups(): HasMany
    {
        return $this->hasMany(WordGroup::class, 'created_by');
    }

    /**
     * Words created by the user, in any group.
     *
     * @return HasMany<Word, $this>
     */
    public function words(): HasMany
    {
        return $this->hasMany(Word::class, 'created_by');
    }

    /**
     * Group collections created by the user.
     *
     * @return HasMany<GroupCollection, $this>
     */
    public function groupCollections(): HasMany
    {
        return $this->hasMany(GroupCollection::class, 'created_by');
    }

    /**
     * @return BelongsToMany<WordGroup, $this>
     */
    public function favoriteWordGroups(): BelongsToMany
    {
        return $this->belongsToMany(WordGroup::class, 'favorite_word_groups')->withTimestamps();
    }

    /**
     * @return BelongsToMany<GroupCollection, $this>
     */
    public function favoriteGroupCollections(): BelongsToMany
    {
        return $this->belongsToMany(GroupCollection::class, 'favorite_group_collections')->withTimestamps();
    }

    /**
     * Private groups of other users this user was granted access to.
     *
     * @return BelongsToMany<WordGroup, $this>
     */
    public function sharedWordGroups(): BelongsToMany
    {
        return $this->belongsToMany(WordGroup::class, 'word_group_members')->withTimestamps();
    }

    /**
     * Private collections of other users this user was granted access to.
     *
     * @return BelongsToMany<GroupCollection, $this>
     */
    public function sharedGroupCollections(): BelongsToMany
    {
        return $this->belongsToMany(GroupCollection::class, 'group_collection_members')->withTimestamps();
    }
}
