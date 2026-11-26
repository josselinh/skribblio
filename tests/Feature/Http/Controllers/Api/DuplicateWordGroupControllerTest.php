<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\Visibility;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateWordGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $wordGroup = WordGroup::factory()->public()->create();

        $this->postJson("/api/word-groups/{$wordGroup->id}/duplicate")->assertUnauthorized();
    }

    public function test_copies_a_public_group_into_a_private_group_closed_to_contributions_owned_by_the_user(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create(['name' => 'Animaux']);
        Word::factory()->for($wordGroup)->create(['name' => 'chat']);
        Word::factory()->for($wordGroup)->create(['name' => 'chien']);

        $response = $this->actingAs($user)->postJson("/api/word-groups/{$wordGroup->id}/duplicate");

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Animaux (copie)')
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.accepts_contributions', false)
            ->assertJsonPath('data.can.update', true);
        $copy = WordGroup::findOrFail($response->json('data.id'));
        $this->assertTrue($copy->isCreatedBy($user));
        $this->assertSame(Visibility::Private, $copy->visibility);
        $this->assertEqualsCanonicalizing(['chat', 'chien'], $copy->words()->pluck('name')->all());
        $this->assertSame([$user->id], $copy->words()->distinct()->pluck('created_by')->all());
        $this->assertSame(2, $wordGroup->words()->count());
    }

    public function test_does_not_copy_members_favorites_collections_or_share_link(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->openToContributions()->createdBy($creator)->create();
        $wordGroup->members()->attach($member);
        $wordGroup->favoritedBy()->attach($member);
        GroupCollection::factory()->createdBy($creator)->hasAttached($wordGroup)->create();
        $wordGroup->regenerateShareToken();

        $response = $this->actingAs($member)->postJson("/api/word-groups/{$wordGroup->id}/duplicate");

        $response->assertCreated()->assertJsonPath('data.is_favorite', false);
        $copy = WordGroup::findOrFail($response->json('data.id'));
        $this->assertTrue($copy->isCreatedBy($member));
        $this->assertSame(0, $copy->members()->count());
        $this->assertSame(0, $copy->favoritedBy()->count());
        $this->assertSame(0, $copy->groupCollections()->count());
        $this->assertNull($copy->share_token);
    }

    public function test_copy_is_kept_when_the_original_and_its_word_authors_are_deleted(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->create();
        Word::factory()->for($wordGroup)->createdBy($author)->create(['name' => 'chat']);

        $copyId = $this->actingAs($user)->postJson("/api/word-groups/{$wordGroup->id}/duplicate")->json('data.id');
        $author->delete();
        $wordGroup->creator->delete();

        $this->assertModelMissing($wordGroup);
        $this->assertSame(['chat'], WordGroup::findOrFail($copyId)->words()->pluck('name')->all());
    }

    public function test_truncates_a_long_name_to_fit_the_suffix(): void
    {
        $wordGroup = WordGroup::factory()->public()->create(['name' => str_repeat('a', 255)]);

        $response = $this->actingAs(User::factory()->create())->postJson("/api/word-groups/{$wordGroup->id}/duplicate");

        $response->assertCreated()->assertJsonPath('data.name', str_repeat('a', 247).' (copie)');
    }

    public function test_returns_404_for_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->postJson("/api/word-groups/{$wordGroup->id}/duplicate")
            ->assertNotFound();

        $this->assertDatabaseCount('word_groups', 1);
    }
}
