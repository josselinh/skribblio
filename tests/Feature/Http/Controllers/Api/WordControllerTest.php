<?php

namespace Tests\Feature\Http\Controllers\Api;

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
            ->assertJsonPath('data.0.can', ['update' => false, 'delete' => false]);
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

    public function test_index_returns_404_for_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->getJson("/api/word-groups/{$wordGroup->id}/words")
            ->assertNotFound();
    }

    public function test_store_lets_any_user_add_a_word_to_a_public_group_and_touches_the_group(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->create(['updated_at' => now()->subWeek()]);

        $response = $this->actingAs($user)->postJson("/api/word-groups/{$wordGroup->id}/words", ['name' => 'chat']);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'chat')
            ->assertJsonPath('data.creator.id', $user->id)
            ->assertJsonPath('data.can', ['update' => true, 'delete' => true]);
        $this->assertDatabaseHas('words', ['word_group_id' => $wordGroup->id, 'name' => 'chat', 'created_by' => $user->id]);
        $this->assertTrue($wordGroup->fresh()->updated_at->isToday());
    }

    public function test_store_returns_404_for_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['name' => 'chat'])
            ->assertNotFound();

        $this->assertDatabaseCount('words', 0);
    }

    public function test_store_rejects_word_already_in_the_group_regardless_of_case_with_422(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        Word::factory()->for($wordGroup)->create(['name' => 'chat']);

        $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['name' => 'Chat'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'Ce mot est déjà dans le groupe.']);
    }

    public function test_store_with_empty_name_returns_422(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['name' => '  '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'Le champ nom est obligatoire.']);
    }

    public function test_store_accepts_a_word_of_32_characters_with_spaces(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['name' => 'tour '.str_repeat('e', 27)])
            ->assertCreated();
    }

    public function test_store_rejects_word_longer_than_32_characters_with_422(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['name' => str_repeat('a', 33)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_store_rejects_word_containing_a_comma_with_422(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", ['name' => 'chat, chien'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'Un mot ne peut pas contenir de virgule : Skribbl.io s’en sert comme séparateur.']);
    }

    public function test_update_renames_word_for_its_author(): void
    {
        $author = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->public())->createdBy($author)->create(['name' => 'chta']);

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
}
