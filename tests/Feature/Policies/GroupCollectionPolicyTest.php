<?php

namespace Tests\Feature\Policies;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GroupCollectionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_only_a_public_collection(): void
    {
        $this->assertTrue(Gate::forUser(null)->allows('view', GroupCollection::factory()->public()->create()));
        $this->assertFalse(Gate::forUser(null)->allows('view', GroupCollection::factory()->private()->create()));
    }

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

    public function test_open_collection_lets_its_viewers_add_groups_they_can_see_including_their_private_ones(): void
    {
        $contributor = User::factory()->create();
        $groupCollection = GroupCollection::factory()->public()->openToContributions()->create();
        $ownPrivateGroup = WordGroup::factory()->private()->createdBy($contributor)->create();

        $this->assertTrue($contributor->can('addWordGroup', $groupCollection));
        $this->assertTrue($contributor->can('attachWordGroup', [$groupCollection, $ownPrivateGroup]));
        $this->assertFalse($contributor->can('attachWordGroup', [$groupCollection, WordGroup::factory()->private()->create()]));
    }

    public function test_open_private_collection_does_not_let_users_who_cannot_see_it_add_groups(): void
    {
        $groupCollection = GroupCollection::factory()->private()->openToContributions()->create();

        $this->assertFalse(User::factory()->create()->can('addWordGroup', $groupCollection));
    }

    public function test_collection_open_to_members_only_lets_only_its_creator_and_members_add_groups(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $groupCollection = GroupCollection::factory()->openToMembersOnly()->createdBy($creator)->create();
        $groupCollection->members()->attach($member);
        $otherUser = User::factory()->create();

        $this->assertTrue($otherUser->can('view', $groupCollection));
        $this->assertFalse($otherUser->can('addWordGroup', $groupCollection));
        $this->assertTrue($member->can('addWordGroup', $groupCollection));
        $this->assertTrue($creator->can('share', $groupCollection));
    }

    public function test_revoked_member_can_no_longer_remove_the_groups_they_added(): void
    {
        $member = User::factory()->create();
        $groupCollection = GroupCollection::factory()->openToMembersOnly()->create();
        $groupCollection->members()->attach($member);
        $wordGroup = WordGroup::factory()->public()->create();
        $groupCollection->wordGroups()->attach($wordGroup, ['added_by' => $member->id]);

        $this->assertTrue($member->can('detachWordGroup', [$groupCollection, $wordGroup]));

        $groupCollection->revokeAccess($member);

        $this->assertFalse($member->can('detachWordGroup', [$groupCollection->fresh(), $wordGroup]));
    }

    public function test_only_members_can_leave_a_collection(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $groupCollection = GroupCollection::factory()->openToMembersOnly()->createdBy($creator)->create();
        $groupCollection->members()->attach($member);

        $this->assertTrue($member->can('leave', $groupCollection));
        $this->assertFalse($creator->can('leave', $groupCollection));
        $this->assertFalse(User::factory()->create()->can('leave', $groupCollection));
    }

    public function test_member_with_a_full_sharing_removes_groups_added_by_others(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->openToMembersOnly()->createdBy($creator)->create();
        $groupCollection->members()->attach($fullMember = User::factory()->create(), ['share_type' => 'full']);
        $groupCollection->members()->attach($partialMember = User::factory()->create());
        $wordGroup = WordGroup::factory()->public()->create();
        $groupCollection->wordGroups()->attach($wordGroup, ['added_by' => $creator->id]);

        $this->assertTrue($fullMember->can('detachWordGroup', [$groupCollection, $wordGroup]));
        $this->assertFalse($partialMember->can('detachWordGroup', [$groupCollection, $wordGroup]));
        $this->assertFalse($fullMember->can('update', $groupCollection));
    }
}
