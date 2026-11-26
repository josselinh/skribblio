<?php

namespace Tests\Feature\Models;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharingVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_sees_private_group_and_can_add_words_but_not_manage_it(): void
    {
        $member = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->openToContributions()->create();
        $wordGroup->members()->attach($member);

        $this->assertTrue($wordGroup->fresh()->isVisibleTo($member));
        $this->assertTrue(WordGroup::visibleTo($member)->whereKey($wordGroup->id)->exists());
        $this->assertTrue($member->can('addWord', $wordGroup->fresh()));
        $this->assertFalse($member->can('update', $wordGroup->fresh()));
    }

    public function test_collection_member_does_not_see_the_private_groups_of_the_collection_creator_until_granted(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $privateGroup = WordGroup::factory()->private()->openToContributions()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->private()->openToContributions()->createdBy($creator)->hasAttached($privateGroup)->create();
        $groupCollection->members()->attach($member);

        $this->assertFalse($privateGroup->fresh()->isVisibleTo($member));
        $this->assertFalse(WordGroup::visibleTo($member)->whereKey($privateGroup->id)->exists());

        $privateGroup->members()->attach($member);

        $this->assertTrue($privateGroup->fresh()->isVisibleTo($member));
        $this->assertTrue(WordGroup::visibleTo($member)->whereKey($privateGroup->id)->exists());
    }

    public function test_collection_member_does_not_see_private_groups_of_other_users_in_the_collection(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $carol = User::factory()->create();
        $alicesGroup = WordGroup::factory()->private()->openToContributions()->createdBy($alice)->create();
        $alicesGroup->members()->attach($bob);
        $bobsCollection = GroupCollection::factory()->private()->openToContributions()->createdBy($bob)->hasAttached($alicesGroup)->create();
        $bobsCollection->members()->attach($carol);

        $this->assertTrue($alicesGroup->fresh()->isVisibleTo($bob));
        $this->assertFalse($alicesGroup->fresh()->isVisibleTo($carol));
        $this->assertFalse(WordGroup::visibleTo($carol)->whereKey($alicesGroup->id)->exists());
    }

    public function test_revoking_collection_access_removes_access_to_its_private_groups(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $privateGroup = WordGroup::factory()->private()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->private()->createdBy($creator)->hasAttached($privateGroup)->create();
        $groupCollection->members()->attach($member);

        $groupCollection->revokeAccess($member);

        $this->assertFalse($privateGroup->fresh()->isVisibleTo($member));
    }

    public function test_members_of_a_public_collection_do_not_see_its_private_groups(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $privateGroup = WordGroup::factory()->private()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->openToMembersOnly()->createdBy($creator)->hasAttached($privateGroup)->create();
        $groupCollection->members()->attach($member);

        $this->assertFalse($privateGroup->fresh()->isVisibleTo($member));
        $this->assertFalse(WordGroup::visibleTo($member)->whereKey($privateGroup->id)->exists());
    }

    public function test_members_no_longer_see_a_private_collection_closed_to_contributions_until_it_reopens(): void
    {
        $member = User::factory()->create();
        $groupCollection = GroupCollection::factory()->private()->openToContributions()->create();
        $groupCollection->members()->attach($member);

        $groupCollection->update(['accepts_contributions' => false]);

        $this->assertFalse($groupCollection->fresh()->isVisibleTo($member));
        $this->assertFalse(GroupCollection::visibleTo($member)->whereKey($groupCollection->id)->exists());
        $this->assertTrue($groupCollection->fresh()->isVisibleTo($groupCollection->creator));

        $groupCollection->update(['accepts_contributions' => true]);

        $this->assertTrue($groupCollection->fresh()->isVisibleTo($member));
        $this->assertTrue(GroupCollection::visibleTo($member)->whereKey($groupCollection->id)->exists());
    }
}
