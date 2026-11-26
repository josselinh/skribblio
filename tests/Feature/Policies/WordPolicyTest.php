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

    public function test_whoever_can_see_the_group_votes_open_or_closed_author_included(): void
    {
        $author = User::factory()->create();
        $openWord = Word::factory()->for(WordGroup::factory()->public()->openToContributions())->createdBy($author)->create();
        $closedWord = Word::factory()->for(WordGroup::factory()->public()->closedToContributions())->createdBy($author)->create();

        $this->assertTrue(User::factory()->create()->can('vote', $openWord));
        $this->assertTrue(User::factory()->create()->can('vote', $closedWord));
        $this->assertTrue($author->can('vote', $openWord));
    }

    public function test_users_who_cannot_see_the_group_cannot_vote(): void
    {
        $word = Word::factory()->for(WordGroup::factory()->private()->openToContributions())->create();

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

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_member_with_a_full_sharing_manages_every_word_while_the_group_is_open(string $ability): void
    {
        $groupCreator = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->openToContributions()->createdBy($groupCreator)->create();
        $wordGroup->members()->attach($fullMember = User::factory()->create(), ['share_type' => 'full']);
        $wordGroup->members()->attach($partialMember = User::factory()->create(), ['share_type' => 'partial']);
        $word = Word::factory()->for($wordGroup)->createdBy($groupCreator)->create();

        $this->assertTrue($fullMember->can($ability, $word));
        $this->assertTrue($fullMember->can('manageContributions', $wordGroup));
        $this->assertFalse($partialMember->can($ability, $word));
        $this->assertFalse($partialMember->can('manageContributions', $wordGroup));

        $wordGroup->update(['accepts_contributions' => false]);

        $this->assertFalse($fullMember->can($ability, $word->fresh()));
    }

    public function test_full_sharing_no_longer_applies_once_the_group_is_open_to_everyone(): void
    {
        $wordGroup = WordGroup::factory()->openToMembersOnly()->create();
        $wordGroup->members()->attach($member = User::factory()->create(), ['share_type' => 'full']);
        $word = Word::factory()->for($wordGroup)->createdBy($wordGroup->creator)->create();

        $this->assertTrue($member->can('update', $word));

        $wordGroup->update(['contributions_members_only' => false]);

        $this->assertFalse($member->can('update', $word->fresh()));
    }
}
