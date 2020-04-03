<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Group;
use App\Models\Sentence;

class SentencesSeeder extends Seeder
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
            $groups = $user->groups()->get();

            $groups->each(function (Group $group) use ($user) {
                factory(Sentence::class, 4)->create(['user_id' => $user->id, 'group_id' => $group->id]);
            });
        });
    }
}
