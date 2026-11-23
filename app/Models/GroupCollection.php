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
     * their own groups, which are kept as they are. Hidden groups are not copied, and neither are
     * favorites, members or the share link. The copy is empty when no group is visible.
     */
    public function duplicateFor(User $user): self
    {
        return DB::transaction(function () use ($user): self {
            $copy = $user->groupCollections()->create([
                'name' => $this->copyName(),
                'language' => $this->language,
                'visibility' => Visibility::Private,
                'accepts_contributions' => false,
                'contributions_members_only' => false,
            ]);

            $wordGroupIds = $this->wordGroups()->visibleTo($user)->get()
                ->map(fn (WordGroup $wordGroup): string => $wordGroup->isCreatedBy($user)
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
        return $this->belongsToMany(WordGroup::class, 'groups_collections')->withPivot('added_by')->withTimestamps();
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
     * Once the user left: removes their favorite on the collection when they can no longer see it, and on
     * its groups hidden from them since (the private groups of its creator, which it showed to them).
     */
    protected function removeFavoritesHiddenFrom(User $user): void
    {
        if (! $this->fresh()->isVisibleTo($user)) {
            $this->favoritedBy()->detach($user);
        }

        $user->favoriteWordGroups()->detach(
            $this->wordGroups()
                ->whereNotIn('word_groups.id', WordGroup::query()->visibleTo($user)->select('word_groups.id'))
                ->pluck('word_groups.id'),
        );
    }

    /**
     * Users granted access to this collection: to see it while private (they also see the private groups
     * of the collection's creator that it contains), to contribute to it while public and open to its
     * members only.
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
