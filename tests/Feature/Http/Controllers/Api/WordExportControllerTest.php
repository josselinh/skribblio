<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $wordGroup = WordGroup::factory()->public()->create();

        $this->getJson("/api/word-groups/{$wordGroup->id}/export")->assertUnauthorized();
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
        $this->assertContains('chat', $response->json('data'));
        $this->assertNotContains('autre groupe', $response->json('data'));
        $this->assertSame($wordGroup->words()->orderBy('name')->pluck('name')->all(), $response->json('data'));
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
            ->assertExactJson(['data' => ['chat']]);
    }

    public function test_group_collection_export_returns_words_of_visible_groups_only(): void
    {
        $user = User::factory()->create();
        $firstGroup = WordGroup::factory()->public()->create();
        $secondGroup = WordGroup::factory()->createdBy($user)->private()->create();
        $hiddenGroup = WordGroup::factory()->private()->create();
        Word::factory()->for($firstGroup)->create(['name' => 'chat']);
        Word::factory()->for($secondGroup)->create(['name' => 'arbre']);
        Word::factory()->for($secondGroup)->create(['name' => 'chat']);
        Word::factory()->for($hiddenGroup)->create(['name' => 'secret']);
        $groupCollection = GroupCollection::factory()->public()->create();
        $groupCollection->wordGroups()->attach([$firstGroup->id, $secondGroup->id, $hiddenGroup->id]);

        $this->actingAs($user)
            ->getJson("/api/group-collections/{$groupCollection->id}/export")
            ->assertOk()
            ->assertExactJson(['data' => ['arbre', 'chat', 'chat']]);
    }

    public function test_group_collection_export_returns_404_for_private_collection_of_another_user(): void
    {
        $groupCollection = GroupCollection::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->getJson("/api/group-collections/{$groupCollection->id}/export")
            ->assertNotFound();
    }

    public function test_composition_combines_selected_groups_and_groups_of_selected_collections(): void
    {
        $user = User::factory()->create();
        $selectedGroup = WordGroup::factory()->public()->create(['name' => 'Animaux']);
        $sharedGroup = WordGroup::factory()->createdBy($user)->create(['name' => 'Fruits']);
        $otherCollectionGroup = WordGroup::factory()->public()->create(['name' => 'Pays']);
        $hiddenGroup = WordGroup::factory()->private()->create();
        Word::factory()->for($selectedGroup)->create(['name' => 'chat']);
        Word::factory()->for($sharedGroup)->create(['name' => 'pomme']);
        Word::factory()->for($otherCollectionGroup)->create(['name' => 'France']);
        Word::factory()->for($hiddenGroup)->create(['name' => 'secret']);
        Word::factory()->create(['name' => 'ailleurs']);
        $firstCollection = GroupCollection::factory()->public()->hasAttached([$sharedGroup, $hiddenGroup])->create();
        $secondCollection = GroupCollection::factory()->createdBy($user)->hasAttached([$sharedGroup, $otherCollectionGroup])->create();

        $response = $this->actingAs($user)->getJson('/api/exports/composition?'.http_build_query([
            'word_group_ids' => [$selectedGroup->id, $sharedGroup->id],
            'group_collection_ids' => [$firstCollection->id, $secondCollection->id],
        ]));

        $response->assertOk()
            ->assertJsonPath('data.word_group_ids', [$selectedGroup->id, $sharedGroup->id, $otherCollectionGroup->id])
            ->assertJsonPath('data.hidden_word_groups_count', 1);
        $this->assertEqualsCanonicalizing(['chat', 'pomme', 'France'], $response->json('data.words'));
    }

    public function test_composition_requires_a_group_or_a_collection(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/exports/composition')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word_group_ids' => 'Choisissez au moins un groupe ou une collection.']);
    }

    public function test_composition_rejects_items_hidden_from_the_user(): void
    {
        $privateCollection = GroupCollection::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/exports/composition?'.http_build_query(['group_collection_ids' => [$privateCollection->id]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items' => 'Un ou plusieurs éléments sélectionnés sont introuvables.']);
    }
}
