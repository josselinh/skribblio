<?php

namespace Tests\Feature\Policies;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WordGroupPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_only_a_public_group(): void
    {
        $this->assertTrue(Gate::forUser(null)->allows('view', WordGroup::factory()->public()->create()));
        $this->assertFalse(Gate::forUser(null)->allows('view', WordGroup::factory()->private()->create()));
    }

    #[TestWith(['view'])]
    #[TestWith(['addWord'])]
    #[TestWith(['favorite'])]
    public function test_public_group_allows_any_user(string $ability): void
    {
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();

        $this->assertTrue(User::factory()->create()->can($ability, $wordGroup));
    }

    #[TestWith(['view'])]
    #[TestWith(['addWord'])]
    #[TestWith(['favorite'])]
    public function test_private_group_allows_only_its_creator(string $ability): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create();

        $this->assertTrue($creator->can($ability, $wordGroup));
        $this->assertFalse(User::factory()->create()->can($ability, $wordGroup));
    }

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_only_creator_can_manage_even_a_public_group(string $ability): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->createdBy($creator)->create();

        $this->assertTrue($creator->can($ability, $wordGroup));
        $this->assertFalse(User::factory()->create()->can($ability, $wordGroup));
    }

    public function test_closed_group_lets_only_its_creator_add_words(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $publicGroup = WordGroup::factory()->public()->closedToContributions()->createdBy($creator)->create();
        $privateGroup = WordGroup::factory()->private()->closedToContributions()->createdBy($creator)->create();
        $privateGroup->members()->attach($member);

        $this->assertTrue($creator->can('addWord', $publicGroup));
        $this->assertFalse(User::factory()->create()->can('addWord', $publicGroup));
        $this->assertTrue($member->can('view', $privateGroup));
        $this->assertFalse($member->can('addWord', $privateGroup));
    }

    public function test_open_private_group_lets_its_members_add_words(): void
    {
        $member = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->openToContributions()->create();
        $wordGroup->members()->attach($member);

        $this->assertTrue($member->can('addWord', $wordGroup));
    }

    public function test_group_open_to_members_only_lets_only_its_creator_and_members_add_words_and_be_shared(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $wordGroup = WordGroup::factory()->openToMembersOnly()->createdBy($creator)->create();
        $wordGroup->members()->attach($member);
        $otherUser = User::factory()->create();

        $this->assertTrue($otherUser->can('view', $wordGroup));
        $this->assertFalse($otherUser->can('addWord', $wordGroup));
        $this->assertTrue($member->can('addWord', $wordGroup));
        $this->assertTrue($creator->can('addWord', $wordGroup));
        $this->assertTrue($creator->can('share', $wordGroup));
    }

    public function test_members_only_option_has_no_effect_once_the_group_is_open_to_everyone_or_closed(): void
    {
        $creator = User::factory()->create();
        $openToEveryone = WordGroup::factory()->public()->openToContributions()->createdBy($creator)->create(['contributions_members_only' => false]);
        $closed = WordGroup::factory()->public()->closedToContributions()->createdBy($creator)->create(['contributions_members_only' => true]);

        $this->assertTrue(User::factory()->create()->can('addWord', $openToEveryone));
        $this->assertFalse($creator->can('share', $openToEveryone));
        $this->assertFalse($creator->can('share', $closed));
    }

    public function test_only_direct_members_can_leave_a_group(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $collectionMember = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create();
        $wordGroup->members()->attach($member);
        $groupCollection = GroupCollection::factory()->private()->createdBy($creator)->create();
        $groupCollection->wordGroups()->attach($wordGroup);
        $groupCollection->members()->attach($collectionMember);

        $this->assertTrue($member->can('leave', $wordGroup));
        $this->assertFalse($creator->can('leave', $wordGroup));
        $this->assertTrue($collectionMember->can('view', $wordGroup));
        $this->assertFalse($collectionMember->can('leave', $wordGroup));
        $this->assertFalse(User::factory()->create()->can('leave', WordGroup::factory()->public()->create()));
    }
}
