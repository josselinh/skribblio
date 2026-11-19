<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\WordVote;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $wordGroup = WordGroup::factory()->public()->create();

        $this->getJson("/api/word-groups/{$wordGroup->id}/words")->assertUnauthorized();
    }

    public function test_index_lists_words_of_the_group_most_recent_first_with_their_author(): void
    {
        $author = User::factory()->create(['name' => 'Alice']);
        $wordGroup = WordGroup::factory()->public()->create();
        $olderWord = Word::factory()->for($wordGroup)->create(['created_at' => now()->subDay()]);
        $newerWord = Word::factory()->for($wordGroup)->createdBy($author)->create();
        Word::factory()->create();

        $response = $this->actingAs(User::factory()->create())->getJson("/api/word-groups/{$wordGroup->id}/words");

        $response->assertOk()
            ->assertJsonPath('data.*.id', [$newerWord->id, $olderWord->id])
            ->assertJsonPath('data.0.creator', ['id' => $author->id, 'name' => 'Alice'])
            ->assertJsonPath('data.0.can', ['update' => false, 'delete' => false, 'vote' => false]);
    }

    public function test_index_paginates_by_twenty(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        Word::factory()->count(21)->for($wordGroup)->create();

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words?page=2")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 21);
    }

    public function test_index_returns_the_votes_of_each_word_and_the_vote_of_the_current_user(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();
        $word = Word::factory()->for($wordGroup)->create();
        $word->voteFor($user, WordVote::Down);
        $word->voteFor(User::factory()->create(), WordVote::Up);
        $word->voteFor(User::factory()->create(), WordVote::Up);

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words")
            ->assertOk()
            ->assertJsonPath('data.0.upvotes_count', 2)
            ->assertJsonPath('data.0.downvotes_count', 1)
            ->assertJsonPath('data.0.my_vote', 'down')
            ->assertJsonPath('data.0.can.vote', true)
            ->assertJsonPath('has_votes', true);
    }

    public function test_index_says_when_no_word_of_the_group_received_a_vote(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        Word::factory()->for($wordGroup)->create();
        Word::factory()->create()->voteFor($user, WordVote::Up);

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words")
            ->assertOk()
            ->assertJsonPath('data.0.my_vote', null)
            ->assertJsonPath('has_votes', false);
    }

    public function test_index_sorts_by_score_then_by_most_upvotes(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->public()->openToContributions()->create();
        $disliked = Word::factory()->for($wordGroup)->create(['name' => 'disliked']);
        $unvoted = Word::factory()->for($wordGroup)->create(['name' => 'unvoted']);
        $liked = Word::factory()->for($wordGroup)->create(['name' => 'liked']);
        $controversial = Word::factory()->for($wordGroup)->create(['name' => 'controversial']);
        [$first, $second, $third] = User::factory()->count(3)->create();
        $disliked->voteFor($first, WordVote::Down);
        $liked->voteFor($first, WordVote::Up);
        $controversial->voteFor($first, WordVote::Up);
        $controversial->voteFor($second, WordVote::Up);
        $controversial->voteFor($third, WordVote::Down);

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words?sort=score")
            ->assertOk()
            ->assertJsonPath('data.*.name', ['controversial', 'liked', 'unvoted', 'disliked']);
        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words?sort=score&direction=asc")
            ->assertOk()
            ->assertJsonPath('data.*.name', ['disliked', 'unvoted', 'liked', 'controversial']);
    }

    public function test_index_sorts_by_author_name_with_deleted_authors_last_in_both_directions(): void
    {
        $user = User::factory()->create(['name' => 'Mia']);
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        Word::factory()->for($wordGroup)->create(['name' => 'deleted', 'created_by' => null]);
        Word::factory()->for($wordGroup)->createdBy($user)->create(['name' => 'by mia']);
        Word::factory()->for($wordGroup)->createdBy(User::factory()->create(['name' => 'Zoé']))->create(['name' => 'by zoé']);
        Word::factory()->for($wordGroup)->createdBy(User::factory()->create(['name' => 'Alice']))->create(['name' => 'by alice']);

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words?sort=author")
            ->assertOk()
            ->assertJsonPath('data.*.name', ['by alice', 'by mia', 'by zoé', 'deleted']);
        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words?sort=author&direction=desc")
            ->assertOk()
            ->assertJsonPath('data.*.name', ['by zoé', 'by mia', 'by alice', 'deleted']);
    }

    public function test_index_sorts_by_name_alphabetically_by_default_and_in_reverse_on_demand(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        foreach (['banane', 'abricot', 'cerise'] as $name) {
            Word::factory()->for($wordGroup)->create(['name' => $name]);
        }

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words?sort=name")
            ->assertJsonPath('data.*.name', ['abricot', 'banane', 'cerise']);
        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words?sort=name&direction=desc")
            ->assertJsonPath('data.*.name', ['cerise', 'banane', 'abricot']);
    }

    public function test_index_sorts_by_last_update(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        Word::factory()->for($wordGroup)->create(['name' => 'old', 'updated_at' => now()->subDays(2)]);
        Word::factory()->for($wordGroup)->create(['name' => 'recent', 'created_at' => now()->subWeek(), 'updated_at' => now()]);
        Word::factory()->for($wordGroup)->create(['name' => 'middle', 'updated_at' => now()->subDay()]);

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words?sort=updated")
            ->assertJsonPath('data.*.name', ['recent', 'middle', 'old']);
        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words?sort=updated&direction=asc")
            ->assertJsonPath('data.*.name', ['old', 'middle', 'recent']);
    }

    public function test_index_rejects_an_unknown_sort_with_422(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/words?sort=random&direction=up")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'direction']);
    }

    public function test_index_returns_404_for_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->getJson("/api/word-groups/{$wordGroup->id}/words")
            ->assertNotFound();
    }

    public function test_store_lets_any_user_add_several_words_to_a_public_group_and_touches_the_group(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create(['updated_at' => now()->subWeek()]);

        $response = $this->actingAs($user)->postJson("/api/word-groups/{$wordGroup->id}/words", ['names' => ['chat', 'chien']]);

        $response->assertCreated()
            ->assertJsonPath('data.*.name', ['chat', 'chien'])
            ->assertJsonPath('data.0.creator.id', $user->id)
            ->assertJsonPath('data.0.can', ['update' => true, 'delete' => true, 'vote' => false])
            ->assertJsonPath('skipped', [])
            ->assertJsonPath('invalid', []);
        $this->assertDatabaseHas('words', ['word_group_id' => $wordGroup->id, 'name' => 'chat', 'created_by' => $user->id]);
        $this->assertDatabaseHas('words', ['word_group_id' => $wordGroup->id, 'name' => 'chien', 'created_by' => $user->id]);
        $this->assertTrue($wordGroup->fresh()->updated_at->isToday());
    }

    public function test_store_returns_404_for_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['names' => ['chat']])
            ->assertNotFound();

        $this->assertDatabaseCount('words', 0);
    }

    public function test_store_returns_403_for_another_user_on_a_group_closed_to_contributions(): void
    {
        $wordGroup = WordGroup::factory()->public()->closedToContributions()->create();

        $this->actingAs(User::factory()->create())
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['names' => ['chat']])
            ->assertForbidden();

        $this->assertSame(0, $wordGroup->words()->count());
    }

    public function test_store_skips_words_already_in_the_group_or_repeated_regardless_of_case_and_adds_the_others(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        Word::factory()->for($wordGroup)->create(['name' => 'chat']);

        $response = $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['names' => ['Chat', 'chien', 'CHIEN', 'chien']]);

        $response->assertCreated()
            ->assertJsonPath('data.*.name', ['chien'])
            ->assertJsonPath('skipped', ['Chat', 'CHIEN']);
        $this->assertSame(2, $wordGroup->words()->count());
    }

    public function test_store_reports_invalid_words_and_adds_the_valid_ones(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        $tooLongName = str_repeat('a', 33);

        $response = $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['names' => ['chat', $tooLongName, 'souris, chien', 'tour '.str_repeat('e', 27)]]);

        $response->assertCreated()
            ->assertJsonPath('data.*.name', ['chat', 'tour '.str_repeat('e', 27)])
            ->assertJsonPath('invalid', [
                ['name' => $tooLongName, 'message' => 'Un mot ne peut pas dépasser 32 caractères.'],
                ['name' => 'souris, chien', 'message' => 'Un mot ne peut pas contenir de virgule : Skribbl.io s’en sert comme séparateur.'],
            ]);
    }

    public function test_store_without_any_added_word_returns_200_and_does_not_touch_the_group(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        Word::factory()->for($wordGroup)->create(['name' => 'chat']);
        $wordGroup->forceFill(['updated_at' => now()->subWeek()])->saveQuietly();

        $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['names' => ['chat', str_repeat('a', 33)]])
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->assertFalse($wordGroup->fresh()->updated_at->isToday());
    }

    public function test_store_with_only_empty_names_returns_422(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['names' => ['  ', '']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['names' => 'Saisissez au moins un mot.']);
    }

    public function test_store_rejects_more_than_500_names_with_422(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['names' => array_map(fn (int $index): string => "mot {$index}", range(1, 501))])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['names' => 'Vous pouvez ajouter au plus 500 mots à la fois.']);
        $this->assertSame(0, $wordGroup->words()->count());
    }

    public function test_update_renames_word_for_its_author(): void
    {
        $author = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->public()->openToContributions())->createdBy($author)->create(['name' => 'chta']);

        $this->actingAs($author)
            ->patchJson("/api/words/{$word->id}", ['name' => 'chat'])
            ->assertOk()
            ->assertJsonPath('data.name', 'chat');
        $this->assertSame('chat', $word->fresh()->name);
    }

    public function test_update_accepts_unchanged_name(): void
    {
        $author = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->createdBy($author))->create(['name' => 'chat']);

        $this->actingAs($author)
            ->patchJson("/api/words/{$word->id}", ['name' => 'chat'])
            ->assertOk();
    }

    public function test_update_rejects_name_of_another_word_of_the_group_with_422(): void
    {
        $author = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($author)->create();
        Word::factory()->for($wordGroup)->create(['name' => 'chien']);
        $word = Word::factory()->for($wordGroup)->create(['name' => 'chat']);

        $this->actingAs($author)
            ->patchJson("/api/words/{$word->id}", ['name' => 'chien'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'Ce mot est déjà dans le groupe.']);
    }

    public function test_update_rejects_name_containing_a_comma_with_422(): void
    {
        $author = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->createdBy($author))->create(['name' => 'chat']);

        $this->actingAs($author)
            ->patchJson("/api/words/{$word->id}", ['name' => 'chat,chien'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_update_returns_403_for_user_who_is_neither_author_nor_group_creator(): void
    {
        $word = Word::factory()->for(WordGroup::factory()->public())->create(['name' => 'chat']);

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/words/{$word->id}", ['name' => 'chien'])
            ->assertForbidden();
        $this->assertSame('chat', $word->fresh()->name);
    }

    public function test_destroy_lets_group_creator_delete_a_word_written_by_someone_else(): void
    {
        $groupCreator = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->public()->createdBy($groupCreator))->createdBy(User::factory()->create())->create();

        $this->actingAs($groupCreator)->deleteJson("/api/words/{$word->id}")->assertNoContent();

        $this->assertModelMissing($word);
    }

    public function test_destroy_returns_403_for_user_who_is_neither_author_nor_group_creator(): void
    {
        $word = Word::factory()->for(WordGroup::factory()->public())->create();

        $this->actingAs(User::factory()->create())->deleteJson("/api/words/{$word->id}")->assertForbidden();

        $this->assertModelExists($word);
    }

    public function test_author_can_no_longer_rename_their_word_once_the_group_is_closed_but_the_word_is_kept(): void
    {
        $author = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->closedToContributions()->create();
        $word = Word::factory()->for($wordGroup)->createdBy($author)->create(['name' => 'chat']);

        $this->actingAs($author)->patchJson("/api/words/{$word->id}", ['name' => 'chien'])->assertForbidden();
        $this->actingAs($author)->deleteJson("/api/words/{$word->id}")->assertForbidden();

        $this->assertSame('chat', $word->fresh()->name);
    }
}
