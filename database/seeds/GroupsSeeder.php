<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Group;

class GroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();

        $users->each(function (User $user) {
            factory(Group::class, 3)->create(['user_id' => $user->id]);
        });
    }
}
