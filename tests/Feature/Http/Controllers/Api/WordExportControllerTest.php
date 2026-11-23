<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\WordVote;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_group_export_is_allowed_to_guest_for_a_public_group(): void
    {
        $wordGroup = WordGroup::factory()->public()->create();
        Word::factory()->for($wordGroup)->create(['name' => 'chat']);

        $this->getJson("/api/word-groups/{$wordGroup->id}/export")->assertOk()->assertExactJson(['data' => [['name' => 'chat', 'score' => 0]]]);
    }

    public function test_word_group_export_returns_404_to_guest_for_private_group(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->getJson("/api/word-groups/{$wordGroup->id}/export")->assertNotFound();
    }

    public function test_group_collection_export_returns_401_for_guest_even_on_a_public_collection(): void
    {
        $groupCollection = GroupCollection::factory()->public()->create();

        $this->getJson("/api/group-collections/{$groupCollection->id}/export")->assertUnauthorized();
    }

    public function test_word_group_export_returns_every_word_of_the_group_sorted_by_name(): void
    {
        $wordGroup = WordGroup::factory()->public()->create();
        Word::factory()->for($wordGroup)->create(['name' => 'zèbre']);
        Word::factory()->for($wordGroup)->create(['name' => 'chat']);
        Word::factory()->count(25)->for($wordGroup)->create();
        Word::factory()->create(['name' => 'autre groupe']);

        $response = $this->actingAs(User::factory()->create())->getJson("/api/word-groups/{$wordGroup->id}/export");

        $response->assertOk()->assertJsonCount(27, 'data');
        $names = $response->collect('data')->pluck('name');
        $this->assertContains('chat', $names);
        $this->assertNotContains('autre groupe', $names);
        $this->assertSame($wordGroup->words()->orderBy('name')->pluck('name')->all(), $names->all());
    }

    public function test_word_group_export_returns_the_score_of_each_word(): void
    {
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();
        $disliked = Word::factory()->for($wordGroup)->create(['name' => 'chat']);
        $liked = Word::factory()->for($wordGroup)->create(['name' => 'zèbre']);
        foreach (range(1, 6) as $ignored) {
            $disliked->voteFor(User::factory()->create(), WordVote::Down);
        }
        foreach (range(1, 5) as $ignored) {
            $disliked->voteFor(User::factory()->create(), WordVote::Up);
        }
        $liked->voteFor(User::factory()->create(), WordVote::Up);

        $this->getJson("/api/word-groups/{$wordGroup->id}/export")
            ->assertOk()
            ->assertExactJson(['data' => [['name' => 'chat', 'score' => -1], ['name' => 'zèbre', 'score' => 1]]]);
    }

    public function test_group_collection_export_returns_the_score_of_each_word(): void
    {
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();
        Word::factory()->for($wordGroup)->create(['name' => 'chat'])->voteFor(User::factory()->create(), WordVote::Down);
        $groupCollection = GroupCollection::factory()->public()->hasAttached($wordGroup)->create();

        $this->actingAs(User::factory()->create())
            ->getJson("/api/group-collections/{$groupCollection->id}/export")
            ->assertOk()
            ->assertJsonPath('data.word_groups.0.words', [['name' => 'chat', 'score' => -1]]);
    }

    public function test_word_group_export_returns_404_for_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->getJson("/api/word-groups/{$wordGroup->id}/export")
            ->assertNotFound();
    }

    public function test_word_group_export_is_allowed_to_members_of_a_private_group(): void
    {
        $member = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->create();
        $wordGroup->members()->attach($member);
        Word::factory()->for($wordGroup)->create(['name' => 'chat']);

        $this->actingAs($member)
            ->getJson("/api/word-groups/{$wordGroup->id}/export")
            ->assertOk()
            ->assertExactJson(['data' => [['name' => 'chat', 'score' => 0]]]);
    }

    public function test_group_collection_export_returns_visible_groups_only_with_their_words_sorted_by_name(): void
    {
        $user = User::factory()->create();
        $firstGroup = WordGroup::factory()->public()->create(['name' => 'Bêtes']);
        $secondGroup = WordGroup::factory()->createdBy($user)->private()->create(['name' => 'Arbres']);
        $hiddenGroup = WordGroup::factory()->private()->create();
        Word::factory()->for($firstGroup)->create(['name' => 'chat']);
        Word::factory()->for($secondGroup)->create(['name' => 'arbre']);
        Word::factory()->for($secondGroup)->create(['name' => 'chat']);
        Word::factory()->for($hiddenGroup)->create(['name' => 'secret']);
        $groupCollection = GroupCollection::factory()->public()
            ->hasAttached([$firstGroup, $secondGroup, $hiddenGroup])
            ->create();

        $this->actingAs($user)
            ->getJson("/api/group-collections/{$groupCollection->id}/export")
            ->assertOk()
            ->assertExactJson(['data' => ['hidden_word_groups_count' => 1, 'word_groups' => [
                ['id' => $secondGroup->id, 'name' => 'Arbres', 'words' => [['name' => 'arbre', 'score' => 0], ['name' => 'chat', 'score' => 0]]],
                ['id' => $firstGroup->id, 'name' => 'Bêtes', 'words' => [['name' => 'chat', 'score' => 0]]],
            ]]]);
    }

    public function test_group_collection_export_returns_404_for_private_collection_of_another_user(): void
    {
        $groupCollection = GroupCollection::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->getJson("/api/group-collections/{$groupCollection->id}/export")
            ->assertNotFound();
    }
}
