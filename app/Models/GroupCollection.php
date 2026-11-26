<?php

namespace App\Models;

use App\Enums\Visibility;
use App\Models\Concerns\AcceptsContributions;
use App\Models\Concerns\Favoritable;
use App\Models\Concerns\HasCopyName;
use App\Models\Concerns\HasLanguage;
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
use Illuminate\Support\Facades\DB;

/**
 * A named set of word groups, possibly empty. The groups it contains may have any visibility, including private groups
 * that other viewers of the collection cannot see.
 */
#[Fillable(['name', 'language', 'visibility', 'accepts_contributions', 'contributions_members_only'])]
class GroupCollection extends Model
{
    /** @use HasFactory<GroupCollectionFactory> */
    use AcceptsContributions, Favoritable, HasCopyName, HasFactory, HasLanguage, HasSearchableName, HasUuids, HasVisibility, Shareable;

    /**
     * Default values, matching the database defaults: collections are closed to contributions.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'accepts_contributions' => false,
        'contributions_members_only' => false,
    ];

    /**
     * Copy this collection into a new collection of the user: private, closed to contributions,
     * containing a copy (see `WordGroup::duplicateFor()`) of each group the user can see, except
     * their own groups, which are kept as they are. Without `$copyWordGroups`, it contains the
     * visible groups themselves, none of them copied. Hidden groups are left out, and so are
     * favorites, members and the share link. The copy is empty when no group is visible.
     */
    public function duplicateFor(User $user, bool $copyWordGroups = true): self
    {
        return DB::transaction(function () use ($user, $copyWordGroups): self {
            $copy = $user->groupCollections()->create([
                'name' => $this->copyName(),
                'language' => $this->language,
                'visibility' => Visibility::Private,
                'accepts_contributions' => false,
                'contributions_members_only' => false,
            ]);

            $wordGroupIds = $this->wordGroups()->visibleTo($user)->get()
                ->map(fn (WordGroup $wordGroup): string => ! $copyWordGroups || $wordGroup->isCreatedBy($user)
                    ? $wordGroup->getKey()
                    : $wordGroup->duplicateFor($user)->getKey());
            $copy->wordGroups()->attach($wordGroupIds, ['added_by' => $user->getKey()]);

            return $copy;
        });
    }

    /**
     * The pivot's `added_by` is the user who added the group (null when that user was deleted).
     *
     * @return BelongsToMany<WordGroup, $this>
     */
    public function wordGroups(): BelongsToMany
    {
        return $this->belongsToMany(WordGroup::class, 'groups_collections')->using(GroupCollectionWordGroup::class)->withPivot('added_by')->withTimestamps();
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
     * Users granted access to this collection: to see it and contribute to it while private and open to
     * contributions (its private groups stay hidden from them unless they are given access to each one), to
     * contribute to it while public and open to its members only.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_collection_members')->withPivot('share_type')->withTimestamps();
    }

    /**
     * @return HasMany<GroupCollectionAccessRequest, $this>
     */
    public function accessRequests(): HasMany
    {
        return $this->hasMany(GroupCollectionAccessRequest::class);
    }
}
