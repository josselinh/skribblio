<?php

namespace App\Managers;

use App\Exceptions\Sentence\VoteAlreadyExistsException;
use App\Models\Group;
use App\Models\Sentence;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SentenceManager
{
    /**
     * @param array|null $filters
     * @param int|null $userId
     * @return Collection
     */
    public function export(?array $filters = [], ?int $userId = null): Collection
    {
        return $this->applyFilters(Sentence::query(), $filters, $userId)->get();
    }

    /**
     * @param string $sentence
     * @param int $groupId
     * @param int $userId
     * @return Sentence
     */
    public function add(string $sentence, int $groupId, int $userId): Sentence
    {
        return Sentence::create([
            'sentence' => $sentence,
            'group_id' => $groupId,
            'user_id' => $userId,
        ]);
    }

    /**
     * @param array<int, string> $sentences
     * @return int Number of inserted sentences
     */
    public function addMany(array $sentences, int $groupId, int $userId): int
    {
        // insertOrIgnore bypasses Eloquent: timestamps must be set manually.
        $now = now();

        return Sentence::insertOrIgnore(array_map(fn (string $sentence): array => [
            'sentence' => $sentence,
            'group_id' => $groupId,
            'user_id' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $sentences));
    }

    /**
     * @param int $sentenceId
     * @param int $note
     * @param int $userId
     * @return Vote
     * @throws VoteAlreadyExistsException
     */
    public function vote(int $sentenceId, int $note, int $userId): Vote
    {
        /** Get Sentence @var Sentence $sentence */
        $sentence = Sentence::findOrFail($sentenceId);

        /** Get or create new Vote @var Vote $vote */
        $vote = Vote::firstOrNew(
            ['sentence_id' => $sentence->id, 'user_id' => $userId],
            ['note' => 0]
        );

        // Update the note
        if ($vote->note !== $note) {
            $vote->note = $note;
            $vote->save();

            return $vote;
        }

        throw new VoteAlreadyExistsException();
    }

    /**
     * @param array|null $filters
     * @param int|null $userId
     * @return LengthAwarePaginator
     */
    public function paginate(?array $filters = [], ?int $userId = null): LengthAwarePaginator
    {
        return $this->applyFilters(Sentence::query()->with(['user', 'group', 'votes']), $filters, $userId)->paginate(25);
    }

    /**
     * @param Builder $qb
     * @param array|null $filters
     * @param int|null $userId
     * @return Builder
     */
    private function applyFilters(Builder $qb, ?array $filters = [], ?int $userId = null): Builder
    {
        $user = $userId ? User::find($userId) : null;

        // Visibility
        if ($user?->isAdmin()) {
            // Admin can see all groups
        } else {
            $qb->whereHas('group', function (Builder $where) use ($user) {
                $where->where(function (Builder $_where) {
                    $_where->whereIn('visibility', [Group::VISIBILITY_PUBLIC, Group::VISIBILITY_PUBLIC_UNALTERABLE]);
                });

                if ($user) {
                    $where->orWhere('user_id', $user->id);
                }
            });
        }

        if (!empty($filters['search'])) {
            $qb->where('sentence', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['group'])) {
            $qb->where('group_id', '=', $filters['group']);
        }

        if (!empty($filters['groups'])) {
            $qb->whereIn('group_id', $filters['groups']);
        }

        if (!empty($filters['author'])) {
            $qb->where('user_id', '=', $filters['author']);
        }

        if (!empty($filters['positive_note'])) {
            $qb->where('note', '>=', 0);
        }

        $qb->orderBy('created_at', 'desc');

        return $qb;
    }
}
