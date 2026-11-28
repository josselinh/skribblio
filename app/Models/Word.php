<?php

namespace App\Models;

use App\Enums\SortDirection;
use App\Enums\WordSort;
use App\Enums\WordVote;
use Database\Factories\WordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * A word belongs to exactly one group and has no visibility of its own:
 * it is visible to whoever can see its group. Adding, renaming or deleting
 * a word updates the group's `updated_at`. Its author (`created_by`) becomes null
 * when they delete their account: the word stays in the group. Whoever renamed it last (`updated_by`) is
 * recorded the same way, null until it is renamed.
 *
 * In a group open to contributions, users vote for or against a word (`word_votes`); the votes
 * are an indicator for the contributors, and the export may leave out the words whose score is too low.
 *
 * @property int|null $score
 * @property int|null $upvotes_count
 * @property int|null $downvotes_count
 * @property int|null $my_vote
 */
#[Fillable(['name'])]
#[Touches(['wordGroup'])]
class Word extends Model
{
    /** @use HasFactory<WordFactory> */
    use HasFactory, HasUuids;

    /**
     * Longest word that Skribbl.io accepts in a custom word list.
     */
    public const int NAME_MAX_LENGTH = 32;

    /**
     * @return BelongsTo<WordGroup, $this>
     */
    public function wordGroup(): BelongsTo
    {
        return $this->belongsTo(WordGroup::class);
    }

    /**
     * The author of the word, null once they deleted their account.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user who renamed the word last.
     *
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Renames the word, recording who did it; a name left as it is changes nothing.
     */
    public function renameBy(string $name, User $user): void
    {
        $this->name = $name;
        if (! $this->isDirty('name')) {
            return;
        }
        $this->updater()->associate($user);
        $this->save();
    }

    public function isCreatedBy(User $user): bool
    {
        return $this->created_by === $user->getKey();
    }

    /**
     * Users who voted for or against the word, with the vote as `pivot.value` (+1 or -1).
     *
     * @return BelongsToMany<User, $this>
     */
    public function voters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'word_votes')->withPivot('value')->withTimestamps();
    }

    /**
     * Records the user's vote, replacing their previous one.
     */
    public function voteFor(User $user, WordVote $vote): void
    {
        $this->voters()->syncWithoutDetaching([$user->getKey() => ['value' => $vote->score()]]);
    }

    /**
     * Removes the user's vote, if any.
     */
    public function removeVoteOf(User $user): void
    {
        $this->voters()->detach($user);
    }

    /**
     * Adds `upvotes_count`, `downvotes_count` and the given user's vote (`my_vote`: +1, -1 or null, always
     * null for a guest) to each record, computed in the same query.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withVotes(Builder $query, ?User $user): void
    {
        $query->withCount([
            'voters as upvotes_count' => fn (Builder $query) => $query->where('word_votes.value', 1),
            'voters as downvotes_count' => fn (Builder $query) => $query->where('word_votes.value', -1),
        ]);

        if ($user !== null) {
            $query->addSelect(['my_vote' => self::votesQuery()->select('value')->where('user_id', $user->getKey())]);
        }
    }

    /**
     * Adds the `score` of each record: upvotes minus downvotes, 0 without votes.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withScore(Builder $query): void
    {
        $query->addSelect(['score' => self::votesQuery()->selectRaw('coalesce(sum(value), 0)')]);
    }

    /**
     * Same attributes as the `withVotes` scope, on an already retrieved model.
     */
    public function loadVotes(User $user): static
    {
        $this->loadCount([
            'voters as upvotes_count' => fn (Builder $query) => $query->where('word_votes.value', 1),
            'voters as downvotes_count' => fn (Builder $query) => $query->where('word_votes.value', -1),
        ]);
        $this->setAttribute('my_vote', $this->voters()->whereKey($user->getKey())->value('word_votes.value'));

        return $this;
    }

    /**
     * Orders the words by the given column, then by most recently added.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function orderBySort(Builder $query, WordSort $sort, SortDirection $direction): void
    {
        $direction = $direction->value;

        match ($sort) {
            WordSort::Name => $query->orderBy('words.name', $direction),
            WordSort::Author => $query
                ->orderByRaw('words.created_by is null')
                ->orderBy(User::query()->select('name')->whereColumn('users.id', 'words.created_by'), $direction),
            WordSort::Created => $query->orderBy('words.created_at', $direction),
            WordSort::Updated => $query->orderBy('words.updated_at', $direction),
            WordSort::Score => $query
                ->orderBy(self::votesQuery()->selectRaw('coalesce(sum(value), 0)'), $direction)
                ->orderBy(self::votesQuery()->selectRaw('count(*)')->where('value', 1), $direction),
        };

        $query->latest('words.created_at')->latest('words.id');
    }

    /**
     * The votes of the word being selected, for subqueries.
     */
    private static function votesQuery(): QueryBuilder
    {
        return DB::table('word_votes')->whereColumn('word_votes.word_id', 'words.id');
    }
}
