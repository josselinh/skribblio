<?php

namespace Tests\Feature\Http;

use App\Enums\Locale;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_group_is_in_the_users_content_language_unless_another_one_is_given(): void
    {
        $user = User::factory()->create(['content_locale' => Locale::En]);

        $this->actingAs($user)
            ->postJson('/api/word-groups', ['name' => 'Animals', 'visibility' => 'private'])
            ->assertCreated()
            ->assertJsonPath('data.language', 'en');
        $this->actingAs($user)
            ->postJson('/api/word-groups', ['name' => 'Animaux', 'visibility' => 'private', 'language' => 'fr'])
            ->assertCreated()
            ->assertJsonPath('data.language', 'fr');
    }

    public function test_changing_the_language_of_a_group_removes_it_from_the_collections_in_the_other_language(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->public()->create();
        $otherUsersCollection = GroupCollection::factory()->private()->hasAttached($wordGroup, ['added_by' => null])->create(['updated_at' => now()->subDay()]);

        $this->actingAs($user)
            ->patchJson("/api/word-groups/{$wordGroup->id}", ['name' => $wordGroup->name, 'visibility' => 'public', 'language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.language', 'en');

        $this->assertSame(Locale::En, $wordGroup->fresh()->language);
        $this->assertFalse($otherUsersCollection->wordGroups()->exists());
        $this->assertTrue($otherUsersCollection->fresh()->updated_at->isToday());
    }

    public function test_a_new_collection_takes_the_language_of_its_groups_which_must_all_share_it(): void
    {
        $user = User::factory()->create();
        $englishGroup = WordGroup::factory()->createdBy($user)->inLanguage(Locale::En)->create();
        $frenchGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->postJson('/api/group-collections', ['name' => 'Party', 'visibility' => 'private', 'word_group_ids' => [$englishGroup->id]])
            ->assertCreated()
            ->assertJsonPath('data.language', 'en');
        $this->actingAs($user)
            ->postJson('/api/group-collections', ['name' => 'Mix', 'visibility' => 'private', 'word_group_ids' => [$englishGroup->id, $frenchGroup->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word_group_ids' => 'Les groupes d’une collection doivent tous être en anglais, comme elle.']);
        $this->actingAs($user)
            ->postJson('/api/group-collections', ['name' => 'Fête', 'visibility' => 'private', 'language' => 'fr', 'word_group_ids' => [$englishGroup->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word_group_ids' => 'Les groupes d’une collection doivent tous être en français, comme elle.']);
    }

    public function test_a_group_in_another_language_cannot_be_added_to_a_collection(): void
    {
        $user = User::factory()->create();
        $groupCollection = GroupCollection::factory()->createdBy($user)->create();
        $englishGroup = WordGroup::factory()->createdBy($user)->inLanguage(Locale::En)->create();

        $this->actingAs($user)
            ->postJson("/api/group-collections/{$groupCollection->id}/word-groups", ['word_group_id' => $englishGroup->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word_group_id' => 'Ce groupe est en anglais, alors que cette collection est en français.']);

        $this->assertFalse($groupCollection->wordGroups()->whereKey($englishGroup->id)->exists());
    }

    public function test_a_collection_only_changes_language_when_all_its_groups_are_in_the_new_one(): void
    {
        $user = User::factory()->create();
        $groupCollection = GroupCollection::factory()->createdBy($user)->create();
        $payload = ['name' => $groupCollection->name, 'visibility' => 'private', 'language' => 'en'];

        $this->actingAs($user)
            ->patchJson("/api/group-collections/{$groupCollection->id}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['language' => 'Une collection ne peut passer en anglais que si tous ses groupes sont en anglais.']);

        $groupCollection->wordGroups()->first()->delete();

        $this->actingAs($user)
            ->patchJson("/api/group-collections/{$groupCollection->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.language', 'en');
    }

    public function test_searches_can_be_limited_to_one_language(): void
    {
        $user = User::factory()->create();
        $frenchGroup = WordGroup::factory()->public()->create(['name' => 'Animaux']);
        $englishGroup = WordGroup::factory()->public()->inLanguage(Locale::En)->create(['name' => 'Animals']);
        $englishCollection = GroupCollection::factory()->public()->inLanguage(Locale::En)->create(['name' => 'Animals']);
        GroupCollection::factory()->public()->create(['name' => 'Animaux']);

        $this->getJson('/api/search/word-groups?q=anim&language=en')->assertOk()->assertJsonPath('data.*.id', [$englishGroup->id]);
        $this->getJson('/api/search/group-collections?q=anim&language=en')->assertOk()->assertJsonPath('data.*.id', [$englishCollection->id]);
        $this->getJson('/api/search/word-groups?q=anim')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($user)->getJson('/api/word-groups/search?q=anim&language=fr')->assertOk()->assertJsonPath('data.*.id', [$frenchGroup->id]);
        $this->getJson('/api/search/word-groups?language=de')->assertUnprocessable()->assertJsonValidationErrors('language');
    }

    public function test_a_copy_keeps_the_language_and_is_named_in_it(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->inLanguage(Locale::En)->create(['name' => 'Animals']);

        $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/duplicate")
            ->assertCreated()
            ->assertJsonPath('data.name', 'Animals (copy)')
            ->assertJsonPath('data.language', 'en');
    }
}
