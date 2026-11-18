<?php

namespace Database\Seeders;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with a demo user, another user, and their groups,
     * words and collections (mixing public and private visibility).
     */
    public function run(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $otherUser = User::factory()->create();

        $testUserGroups = WordGroup::factory()->count(2)->createdBy($testUser)->public()->create()
            ->concat(WordGroup::factory()->count(2)->createdBy($testUser)->private()->create());
        $otherUserGroups = WordGroup::factory()->count(2)->createdBy($otherUser)->public()->create()
            ->concat(WordGroup::factory()->count(1)->createdBy($otherUser)->private()->create());

        foreach ($testUserGroups->concat($otherUserGroups) as $wordGroup) {
            Word::factory()->count(10)->for($wordGroup)->create();
        }

        GroupCollection::factory()->createdBy($testUser)->public()
            ->hasAttached($testUserGroups->take(3))
            ->create();
        GroupCollection::factory()->createdBy($otherUser)->public()
            ->hasAttached($otherUserGroups)
            ->create();

        $testUser->favoriteWordGroups()->attach($otherUserGroups->first());
    }
}
