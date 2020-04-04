<?php

namespace App\Managers;

use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GroupManager
{
    /**
     * @param string $name
     * @param int $visibility
     * @param int $userId
     * @return Group
     */
    public function add(string $name, int $visibility, int $userId): Group
    {
        return Group::create([
            'name' => ucfirst($name),
            'visibility' => $visibility,
            'user_id' => $userId,
        ]);
    }

    /**
     * @param array|null $filters
     * @return Collection
     */
    public function search(?array $filters = [], ?int $userId = null): Collection
    {
        // Create new query
        $qb = Group::query();

        // Default filters
        // Visibility
        $qb->where(function (Builder $where) use ($userId) {
            $where->orWhereIn('visibility', [1, 2]);

            if (!empty($userId)) {
                $where->orWhere(function (Builder $_where) use ($userId) {
                    $_where->whereIn('visibility', [1, 2, 3]);
                    $_where->where('user_id', $userId);
                });
            }
        });

        if (!empty($filters['search'])) {
            $qb->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['author'])) {
            $qb->where('user_id', '=', $filters['author']);
        }

        $qb->orderBy('created_at', 'desc');

        return $qb->get();
    }
}
