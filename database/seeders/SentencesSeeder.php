<?php

namespace Database\Seeders;

use App\Models\Sentence;
use App\Models\User;
use Illuminate\Database\Seeder;

class SentencesSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function (User $user): void {
            $user->groups->each(function ($group) use ($user): void {
                Sentence::factory(4)->create([
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                ]);
            });
        });
    }
}
