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
use Database\Factories\WordGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'language', 'visibility', 'accepts_contributions', 'contributions_members_only'])]
class WordGroup extends Model
{
    /** @use HasFactory<WordGroupFactory> */
    use AcceptsContributions, Favoritable, HasCopyName, HasFactory, HasLanguage, HasSearchableName, HasUuids, HasVisibility, Shareable;

    /**
     * Default values, matching the database defaults: groups are closed to contributions.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'accepts_contributions' => false,
        'contributions_members_only' => false,
    ];

    /**
     * Number of words inserted per query when copying a group.
     */
    private const int COPY_INSERT_CHUNK_SIZE = 500;

    /**
     * Copy this group into a new group of the user: private, closed to contributions, with every
     * word written by them (so that the copy does not depend on the original authors). Nothing else
     * is copied (favorites, members, share link, collections) and the copy keeps no link to the original.
     */
    public function duplicateFor(User $user): self
    {
        return DB::transaction(function () use ($user): self {
            $copy = $user->wordGroups()->create([
                'name' => $this->copyName(),
                'language' => $this->language,
                'visibility' => Visibility::Private,
                'accepts_contributions' => false,
                'contributions_members_only' => false,
            ]);

            $now = now();
            $this->words()->select('name')->orderBy('id')->chunk(self::COPY_INSERT_CHUNK_SIZE, function ($words) use ($copy, $user, $now): void {
                Word::insert($words->map(fn (Word $word): array => [
                    'id' => $word->newUniqueId(),
                    'name' => $word->name,
                    'word_group_id' => $copy->getKey(),
                    'created_by' => $user->getKey(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });

            return $copy;
        });
    }

    /**
     * @return HasMany<Word, $this>
     */
    public function words(): HasMany
    {
        return $this->hasMany(Word::class);
    }

    /**
     * Once the group's language changed: removes it from the collections in another language (a collection
     * never mixes languages), as deleting it would, even when it was a collection's last group. Those
     * collections are touched.
     */
    public function detachFromCollectionsInOtherLanguages(): void
    {
        $groupCollectionIds = $this->groupCollections()
            ->where('group_collections.language', '!=', $this->language->value)
            ->pluck('group_collections.id');

        if ($groupCollectionIds->isNotEmpty()) {
            $this->groupCollections()->detach($groupCollectionIds);
            GroupCollection::whereKey($groupCollectionIds)->touch();
        }
    }

    /**
     * @return BelongsToMany<GroupCollection, $this>
     */
    public function groupCollections(): BelongsToMany
    {
        return $this->belongsToMany(GroupCollection::class, 'groups_collections')->withPivot('added_by')->withTimestamps();
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
     * Users granted access to this group: to see it while private, to contribute to it while public and
     * open to its members only.
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
     * A private group is also shared with the members of a private collection containing it, when that
     * collection belongs to the group's creator: sharing a collection never exposes other people's groups.
     * The members of a public collection (allowed to contribute to it) do not see its private groups.
     */
    protected function isSharedWith(User $user): bool
    {
        return $this->isMember($user)
            || $this->groupCollections()
                ->where('group_collections.created_by', $this->created_by)
                ->where('group_collections.visibility', Visibility::Private)
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
                ->where('group_collections.visibility', Visibility::Private)
                ->whereHas('members', fn (Builder $query) => $query->whereKey($user->getKey())));
    }
}
