<?php

namespace Tests\Feature\Models;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_visible_to_returns_public_collections_and_own_private_collections_only(): void
    {
        $user = User::factory()->create();
        $ownPrivateGroupCollection = GroupCollection::factory()->private()->createdBy($user)->create();
        $publicGroupCollection = GroupCollection::factory()->public()->create();
        GroupCollection::factory()->private()->create();

        $visibleIds = GroupCollection::visibleTo($user)->pluck('id');

        $this->assertEqualsCanonicalizing([$ownPrivateGroupCollection->id, $publicGroupCollection->id], $visibleIds->all());
    }

    public function test_public_collection_hides_private_groups_of_its_creator_from_other_users(): void
    {
        $creator = User::factory()->create();
        $publicGroup = WordGroup::factory()->public()->createdBy($creator)->create();
        $privateGroup = WordGroup::factory()->private()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->public()->createdBy($creator)->hasAttached([$publicGroup, $privateGroup])->create();

        $visibleToOtherUser = $groupCollection->wordGroups()->visibleTo(User::factory()->create())->pluck('word_groups.id');

        $this->assertSame([$publicGroup->id], $visibleToOtherUser->all());
        $this->assertSame(2, $groupCollection->wordGroups()->visibleTo($creator)->count());
    }

    public function test_group_can_belong_to_several_collections(): void
    {
        $wordGroup = WordGroup::factory()->public()->create();
        GroupCollection::factory()->count(2)->hasAttached($wordGroup)->create();

        $this->assertSame(2, $wordGroup->groupCollections()->count());
    }

    public function test_deleting_collection_keeps_its_groups(): void
    {
        $wordGroup = WordGroup::factory()->create();
        $groupCollection = GroupCollection::factory()->hasAttached($wordGroup)->create();

        $groupCollection->delete();

        $this->assertModelExists($wordGroup);
        $this->assertDatabaseMissing('groups_collections', ['group_collection_id' => $groupCollection->id]);
    }
}
