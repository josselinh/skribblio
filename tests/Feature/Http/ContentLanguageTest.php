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

    public function test_the_language_of_a_group_cannot_be_changed(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->patchJson("/api/word-groups/{$wordGroup->id}", ['name' => $wordGroup->name, 'visibility' => 'private', 'language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['language' => 'La langue d’un groupe ne peut pas être modifiée.']);

        $this->assertSame(Locale::Fr, $wordGroup->fresh()->language);
    }

    public function test_a_new_collection_is_in_the_users_content_language_unless_another_one_is_given(): void
    {
        $user = User::factory()->create(['content_locale' => Locale::En]);

        $this->actingAs($user)
            ->postJson('/api/group-collections', ['name' => 'Party', 'visibility' => 'private'])
            ->assertCreated()
            ->assertJsonPath('data.language', 'en');
        $this->actingAs($user)
            ->postJson('/api/group-collections', ['name' => 'Fête', 'visibility' => 'private', 'language' => 'fr'])
            ->assertCreated()
            ->assertJsonPath('data.language', 'fr');
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

    public function test_the_language_of_a_collection_cannot_be_changed(): void
    {
        $user = User::factory()->create();
        $groupCollection = GroupCollection::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->patchJson("/api/group-collections/{$groupCollection->id}", ['name' => $groupCollection->name, 'visibility' => 'private', 'language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['language' => 'La langue d’une collection ne peut pas être modifiée.']);

        $this->assertSame(Locale::Fr, $groupCollection->fresh()->language);
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
