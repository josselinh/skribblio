<?php

namespace Tests\Feature\Policies;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GroupCollectionPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['view'])]
    #[TestWith(['favorite'])]
    public function test_public_collection_allows_any_user(string $ability): void
    {
        $groupCollection = GroupCollection::factory()->public()->create();

        $this->assertTrue(User::factory()->create()->can($ability, $groupCollection));
    }

    #[TestWith(['view'])]
    #[TestWith(['favorite'])]
    public function test_private_collection_allows_only_its_creator(string $ability): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->private()->createdBy($creator)->create();

        $this->assertTrue($creator->can($ability, $groupCollection));
        $this->assertFalse(User::factory()->create()->can($ability, $groupCollection));
    }

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_only_creator_can_manage_even_a_public_collection(string $ability): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->public()->createdBy($creator)->create();

        $this->assertTrue($creator->can($ability, $groupCollection));
        $this->assertFalse(User::factory()->create()->can($ability, $groupCollection));
    }

    public function test_creator_can_attach_own_groups_and_public_groups_of_others(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)->create();

        $this->assertTrue($creator->can('attachWordGroup', [$groupCollection, WordGroup::factory()->private()->createdBy($creator)->create()]));
        $this->assertTrue($creator->can('attachWordGroup', [$groupCollection, WordGroup::factory()->public()->create()]));
    }

    public function test_creator_cannot_attach_private_group_of_another_user(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)->create();

        $this->assertFalse($creator->can('attachWordGroup', [$groupCollection, WordGroup::factory()->private()->create()]));
    }

    public function test_other_user_cannot_attach_groups_to_a_public_collection(): void
    {
        $otherUser = User::factory()->create();
        $groupCollection = GroupCollection::factory()->public()->create();

        $this->assertFalse($otherUser->can('attachWordGroup', [$groupCollection, WordGroup::factory()->public()->createdBy($otherUser)->create()]));
    }
}
