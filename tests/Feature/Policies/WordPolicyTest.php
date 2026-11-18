<?php

namespace Tests\Feature\Policies;

use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WordPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_is_visible_only_to_users_who_can_see_its_group(): void
    {
        $creator = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->private()->createdBy($creator))->create();

        $this->assertTrue($creator->can('view', $word));
        $this->assertFalse(User::factory()->create()->can('view', $word));
    }

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_word_author_and_group_creator_can_manage_word_but_not_other_users(string $ability): void
    {
        $groupCreator = User::factory()->create();
        $author = User::factory()->create();
        $word = Word::factory()
            ->for(WordGroup::factory()->public()->createdBy($groupCreator))
            ->createdBy($author)
            ->create();

        $this->assertTrue($author->can($ability, $word));
        $this->assertTrue($groupCreator->can($ability, $word));
        $this->assertFalse(User::factory()->create()->can($ability, $word));
    }
}
