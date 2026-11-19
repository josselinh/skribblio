<?php

namespace Tests\Feature\Policies;

use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WordGroupPolicyTest extends TestCase
{
    use RefreshDatabase;

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
}
