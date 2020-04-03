<?php

namespace App\Managers;

use App\Jobs\ImportSentenceJob;
use App\Models\Sentence;
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
     * @param array $filters
     * @return Collection
     */
    public function export(array $filters = []): Collection
    {
        $qb = Sentence::query()->where('note', '>=', 0);

        if (!empty($filters['groups'])) {
            $qb->whereIn('group_id', $filters['groups']);
        }

        if (!empty($filters['users'])) {
            $qb->whereIn('user_id', $filters['users']);
        }

        return $qb->get();
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
     * @return Collection
     */
    public function search(?array $filters = []): Collection
    {
        $qb = Sentence::query()->with(['user', 'group']);

        if (!empty($filters['search'])) {
            $qb->where('sentence', 'like', $filters['search']);

            if (strpos($filters['search'], ' ') === false) {
                $qb->where('sentence', 'not like', '% %');
            }

            if (strpos($filters['search'], '-') === false) {
                $qb->where('sentence', 'not like', '%-%');
            }
        }

        if (!empty($filters['group'])) {
            $qb->where('group_id', '=', $filters['group']);
        }

        if (!empty($filters['author'])) {
            $qb->where('user_id', '=', $filters['author']);
        }

        $qb->orderBy('created_at', 'desc');

        return $qb->get();
    }
}
