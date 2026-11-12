<?php

namespace App\Managers;

use App\Models\Group;
use App\Models\User;
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
     * @param int|null $userId
     * @return Collection
     */
    public function search(?array $filters = [], ?int $userId = null): Collection
    {
        return $this->applyFilters(Group::query()->with('user'), $filters, $userId)->get();
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
            $qb->where(function (Builder $where) use ($filters, $user) {
                $where->when(!empty($filters['writable']) && $filters['writable'] === true, function (Builder $when) {
                    $when->where('visibility', Group::VISIBILITY_PUBLIC);
                }, function (Builder $when) {
                    $when->whereIn('visibility', [Group::VISIBILITY_PUBLIC, Group::VISIBILITY_PUBLIC_UNALTERABLE]);
                });

                if ($user) {
                    $where->orWhere('user_id', $user->id);
                }
            });
        }

        if (!empty($filters['search'])) {
            $qb->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['author'])) {
            $qb->where('user_id', '=', $filters['author']);
        }

        $qb->orderBy('created_at', 'desc');

        return $qb;
    }
}
