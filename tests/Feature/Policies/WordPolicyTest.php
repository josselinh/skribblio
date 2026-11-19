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
            ->for(WordGroup::factory()->public()->openToContributions()->createdBy($groupCreator))
            ->createdBy($author)
            ->create();

        $this->assertTrue($author->can($ability, $word));
        $this->assertTrue($groupCreator->can($ability, $word));
        $this->assertFalse(User::factory()->create()->can($ability, $word));
    }

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_word_author_can_no_longer_manage_word_once_the_group_is_closed(string $ability): void
    {
        $groupCreator = User::factory()->create();
        $author = User::factory()->create();
        $word = Word::factory()
            ->for(WordGroup::factory()->public()->closedToContributions()->createdBy($groupCreator))
            ->createdBy($author)
            ->create();

        $this->assertFalse($author->can($ability, $word));
        $this->assertTrue($groupCreator->can($ability, $word));
    }

    public function test_whoever_can_see_an_open_group_can_vote_except_the_author_of_the_word(): void
    {
        $groupCreator = User::factory()->create();
        $author = User::factory()->create();
        $word = Word::factory()
            ->for(WordGroup::factory()->public()->openToContributions()->createdBy($groupCreator))
            ->createdBy($author)
            ->create();

        $this->assertTrue(User::factory()->create()->can('vote', $word));
        $this->assertTrue($groupCreator->can('vote', $word));
        $this->assertFalse($author->can('vote', $word));
    }

    public function test_nobody_can_vote_once_the_group_is_closed(): void
    {
        $groupCreator = User::factory()->create();
        $word = Word::factory()
            ->for(WordGroup::factory()->public()->closedToContributions()->createdBy($groupCreator))
            ->createdBy(User::factory()->create())
            ->create();

        $this->assertFalse(User::factory()->create()->can('vote', $word));
        $this->assertFalse($groupCreator->can('vote', $word));
    }

    public function test_only_members_can_vote_in_an_open_private_group(): void
    {
        $member = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->openToContributions()->create();
        $wordGroup->members()->attach($member);
        $word = Word::factory()->for($wordGroup)->create();

        $this->assertTrue($member->can('vote', $word));
        $this->assertFalse(User::factory()->create()->can('vote', $word));
    }

    public function test_anyone_can_vote_for_a_word_whose_author_deleted_their_account(): void
    {
        $word = Word::factory()->for(WordGroup::factory()->public()->openToContributions())->create(['created_by' => null]);

        $this->assertTrue(User::factory()->create()->can('vote', $word));
    }

    public function test_anyone_who_sees_a_group_open_to_members_only_can_vote(): void
    {
        $wordGroup = WordGroup::factory()->openToMembersOnly()->create();
        $word = Word::factory()->for($wordGroup)->createdBy(User::factory()->create())->create();

        $this->assertTrue(User::factory()->create()->can('vote', $word));
        $this->assertTrue($wordGroup->creator->can('vote', $word));
    }
}
