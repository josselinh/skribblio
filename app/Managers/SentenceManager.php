<?php

namespace App\Managers;

use App\Jobs\ImportSentenceJob;
use App\Models\Sentence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class SentenceManager
{
    /**
     * @param string $sentences
     * @param int $groupId
     * @param int $userId
     */
    public function import(string $sentences, int $groupId, int $userId): void
    {
        /** @var array $sentences */
        $sentences = array_unique(array_filter(array_map('trim', explode(',', $sentences))));

        foreach ($sentences as $sentence) {
            ImportSentenceJob::dispatch($sentence, $groupId, $userId);
        }
    }

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
     * @param array|null $filters
     * @param int|null $userId
     */
    public function paginate(?array $filters = [], int $userId = null)
    {
        return $this->applyFilters(Sentence::query()->with(['user', 'group']), $filters, $userId)->paginate(25);
    }

    /**
     * @param Builder $qb
     * @param array|null $filters
     * @param int|null $userId
     * @return Builder
     */
    private function applyFilters(Builder $qb, ?array $filters = [], ?int $userId = null): Builder
    {
        $qb->whereHas('group', function ($belongsTo) use ($userId) {
            $belongsTo->where(function (Builder $where) use ($userId) {
                $where->orWhereIn('visibility', [1, 2]);

                if (!empty($userId)) {
                    $where->orWhere(function (Builder $_where) use ($userId) {
                        $_where->whereIn('visibility', [1, 2, 3]);
                        $_where->where('user_id', $userId);
                    });
                }
            });
        });

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
