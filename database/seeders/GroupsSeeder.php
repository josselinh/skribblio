<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupsSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function (User $user): void {
            Group::factory(3)->create(['user_id' => $user->id]);
        });
    }
}
