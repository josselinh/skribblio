<?php

namespace App\Managers;

use App\Models\Group;
use Illuminate\Support\Collection;

class GroupManager
{
    /**
     * @param string $name
     * @param int $userId
     * @return Group
     */
    public function add(string $name, int $userId): Group
    {
        return Group::create([
            'name' => ucfirst($name),
            'user_id' => $userId,
        ]);
    }

    /**
     * @param array|null $filters
     * @return Collection
     */
    public function search(?array $filters = []): Collection
    {
        $qb = Group::query();

        if (!empty($filters['search'])) {
            $qb->where('sentence', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['author'])) {
            $qb->where('user_id', '=', $filters['author']);
        }

        $qb->orderBy('created_at', 'desc');

        return $qb->get();
    }
}
