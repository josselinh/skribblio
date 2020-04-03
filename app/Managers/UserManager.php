<?php

namespace App\Managers;

use App\Models\User;
use Illuminate\Support\Collection;

class UserManager
{
    public function search(): Collection
    {
        $qb = User::query();

        $qb->orderBy('name', 'asc');

        return $qb->get();
    }
}
