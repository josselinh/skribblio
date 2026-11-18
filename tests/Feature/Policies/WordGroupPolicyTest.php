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
        $wordGroup = WordGroup::factory()->public()->create();

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
}
