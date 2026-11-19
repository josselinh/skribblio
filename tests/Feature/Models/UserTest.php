<?php

namespace Tests\Feature\Models;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_user_deletes_everything_they_created_but_their_words_in_other_groups(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        $wordInOwnGroup = Word::factory()->for($wordGroup)->createdBy($user)->create();
        $wordInOtherUsersGroup = Word::factory()->for(WordGroup::factory()->public())->createdBy($user)->create();
        $groupCollection = GroupCollection::factory()->createdBy($user)->create();

        $user->delete();

        $this->assertModelMissing($wordGroup);
        $this->assertModelMissing($wordInOwnGroup);
        $this->assertModelMissing($groupCollection);
        $this->assertNull($wordInOtherUsersGroup->fresh()->created_by);
    }

    public function test_user_can_favorite_groups_and_collections(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->create();
        $groupCollection = GroupCollection::factory()->public()->create();

        $user->favoriteWordGroups()->attach($wordGroup);
        $user->favoriteGroupCollections()->attach($groupCollection);

        $this->assertTrue($wordGroup->favoritedBy->contains($user));
        $this->assertTrue($groupCollection->favoritedBy->contains($user));
    }

    public function test_rejects_the_same_favorite_twice(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->create();
        $user->favoriteWordGroups()->attach($wordGroup);

        $this->expectException(UniqueConstraintViolationException::class);

        $user->favoriteWordGroups()->attach($wordGroup);
    }
}
