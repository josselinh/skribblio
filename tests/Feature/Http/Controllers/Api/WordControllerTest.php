<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\WordVote;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_returns_401_for_guest_on_a_group_open_to_contributions(): void
    {
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();

        $this->postJson("/api/word-groups/{$wordGroup->id}/words", ['names' => ['chat']])->assertUnauthorized();
        $this->assertDatabaseCount('words', 0);
    }

    public function test_index_lists_words_of_a_public_group_to_guest_with_votes_but_without_authors(): void
    {
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();
        $word = Word::factory()->for($wordGroup)->create();
        $word->voteFor(User::factory()->create(), WordVote::Up);

        $this->getJson("/api/word-groups/{$wordGroup->id}/words")
            ->assertOk()
            ->assertJsonPath('data.0.id', $word->id)
            ->assertJsonMissingPath('data.0.creator')
            ->assertJsonPath('data.0.upvotes_count', 1)
            ->assertJsonPath('data.0.my_vote', null)
            ->assertJsonPath('data.0.can', ['update' => false, 'delete' => false, 'vote' => false]);
    }

    public function test_index_returns_404_to_guest_for_private_group(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->getJson("/api/word-groups/{$wordGroup->id}/words")->assertNotFound();
    }

    #[TestWith(['author'])]
    #[TestWith(['score'])]
    public function test_index_rejects_members_only_sorts_from_guest_with_422(string $sort): void
    {
        $wordGroup = WordGroup::factory()->public()->create();

        $this->getJson("/api/word-groups/{$wordGroup->id}/words?sort={$sort}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort' => 'Connectez-vous pour utiliser ce tri.']);
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

    public function test_destroy_many_deletes_the_selected_words_and_touches_the_group(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create(['updated_at' => now()->subDay()]);
        $selectedWords = Word::factory()->count(2)->for($wordGroup)->createdBy(User::factory()->create())->create();
        $keptWord = Word::factory()->for($wordGroup)->create();
        $selectedWords[0]->voteFor(User::factory()->create(), WordVote::Up);

        $this->actingAs($user)
            ->deleteJson("/api/word-groups/{$wordGroup->id}/words", ['word_ids' => $selectedWords->modelKeys()])
            ->assertNoContent();

        $this->assertModelMissing($selectedWords[0]);
        $this->assertModelMissing($selectedWords[1]);
        $this->assertModelExists($keptWord);
        $this->assertTrue($wordGroup->fresh()->updated_at->isToday());
    }

    public function test_destroy_many_lets_the_author_delete_their_own_words_while_the_group_is_open(): void
    {
        $author = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();
        $word = Word::factory()->for($wordGroup)->createdBy($author)->create();

        $this->actingAs($author)
            ->deleteJson("/api/word-groups/{$wordGroup->id}/words", ['word_ids' => [$word->id]])
            ->assertNoContent();

        $this->assertModelMissing($word);
    }

    public function test_destroy_many_returns_403_and_deletes_nothing_when_one_word_cannot_be_deleted(): void
    {
        $author = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();
        $ownWord = Word::factory()->for($wordGroup)->createdBy($author)->create();
        $otherWord = Word::factory()->for($wordGroup)->create();

        $this->actingAs($author)
            ->deleteJson("/api/word-groups/{$wordGroup->id}/words", ['word_ids' => [$ownWord->id, $otherWord->id]])
            ->assertForbidden();

        $this->assertModelExists($ownWord);
        $this->assertModelExists($otherWord);
    }

    public function test_destroy_many_returns_404_for_a_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();
        $word = Word::factory()->for($wordGroup)->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/word-groups/{$wordGroup->id}/words", ['word_ids' => [$word->id]])
            ->assertNotFound();

        $this->assertModelExists($word);
    }

    public function test_destroy_many_returns_422_for_a_word_of_another_group(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        $wordOfAnotherGroup = Word::factory()->for(WordGroup::factory()->createdBy($user))->create();

        $this->actingAs($user)
            ->deleteJson("/api/word-groups/{$wordGroup->id}/words", ['word_ids' => [$wordOfAnotherGroup->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word_ids.0' => 'Un des mots sélectionnés n’est plus dans le groupe.']);

        $this->assertModelExists($wordOfAnotherGroup);
    }

    public function test_destroy_many_returns_422_without_any_word(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->deleteJson("/api/word-groups/{$wordGroup->id}/words", ['word_ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word_ids' => 'Sélectionnez au moins un mot.']);
    }

    public function test_update_many_renames_the_words_and_touches_the_group(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create(['updated_at' => now()->subDay()]);
        $cat = Word::factory()->for($wordGroup)->createdBy(User::factory()->create())->create(['name' => 'chta']);
        $dog = Word::factory()->for($wordGroup)->create(['name' => 'chein']);

        $this->actingAs($user)
            ->patchJson("/api/word-groups/{$wordGroup->id}/words", ['words' => [
                ['id' => $cat->id, 'name' => 'chat'],
                ['id' => $dog->id, 'name' => 'chien'],
            ]])
            ->assertNoContent();

        $this->assertSame('chat', $cat->fresh()->name);
        $this->assertSame('chien', $dog->fresh()->name);
        $this->assertTrue($wordGroup->fresh()->updated_at->isToday());
    }

    public function test_update_many_returns_403_and_renames_nothing_when_one_word_cannot_be_updated(): void
    {
        $author = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();
        $ownWord = Word::factory()->for($wordGroup)->createdBy($author)->create(['name' => 'chat']);
        $otherWord = Word::factory()->for($wordGroup)->create(['name' => 'chien']);

        $this->actingAs($author)
            ->patchJson("/api/word-groups/{$wordGroup->id}/words", ['words' => [
                ['id' => $ownWord->id, 'name' => 'chaton'],
                ['id' => $otherWord->id, 'name' => 'chiot'],
            ]])
            ->assertForbidden();

        $this->assertSame('chat', $ownWord->fresh()->name);
        $this->assertSame('chien', $otherWord->fresh()->name);
    }

    public function test_update_many_returns_404_for_a_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();
        $word = Word::factory()->for($wordGroup)->create();

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/word-groups/{$wordGroup->id}/words", ['words' => [['id' => $word->id, 'name' => 'chat']]])
            ->assertNotFound();
    }

    public function test_update_many_refuses_a_name_already_taken_by_another_word_of_the_group_even_one_renamed_too(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        $cat = Word::factory()->for($wordGroup)->create(['name' => 'chat']);
        $dog = Word::factory()->for($wordGroup)->create(['name' => 'chien']);

        $this->actingAs($user)
            ->patchJson("/api/word-groups/{$wordGroup->id}/words", ['words' => [
                ['id' => $cat->id, 'name' => 'chien'],
                ['id' => $dog->id, 'name' => 'chat'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'words.0.name' => 'Ce mot est déjà dans le groupe.',
                'words.1.name' => 'Ce mot est déjà dans le groupe.',
            ]);

        $this->assertSame('chat', $cat->fresh()->name);
    }

    public function test_update_many_refuses_the_same_new_name_twice(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        [$first, $second] = Word::factory()->count(2)->for($wordGroup)->create();

        $this->actingAs($user)
            ->patchJson("/api/word-groups/{$wordGroup->id}/words", ['words' => [
                ['id' => $first->id, 'name' => 'chat'],
                ['id' => $second->id, 'name' => 'Chat'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['words.1.name' => 'Vous avez saisi ce mot plusieurs fois.']);
    }

    public function test_update_many_refuses_a_name_with_a_comma(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        $word = Word::factory()->for($wordGroup)->create();

        $this->actingAs($user)
            ->patchJson("/api/word-groups/{$wordGroup->id}/words", ['words' => [['id' => $word->id, 'name' => 'chat, chien']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'words.0.name' => 'Un mot ne peut pas contenir de virgule : Skribbl.io s’en sert comme séparateur.',
            ]);
    }

    public function test_update_many_returns_422_for_a_word_of_another_group(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        $wordOfAnotherGroup = Word::factory()->for(WordGroup::factory()->createdBy($user))->create(['name' => 'chat']);

        $this->actingAs($user)
            ->patchJson("/api/word-groups/{$wordGroup->id}/words", ['words' => [['id' => $wordOfAnotherGroup->id, 'name' => 'chien']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['words.0.id' => 'Ce mot n’est plus dans le groupe.']);

        $this->assertSame('chat', $wordOfAnotherGroup->fresh()->name);
    }
}
