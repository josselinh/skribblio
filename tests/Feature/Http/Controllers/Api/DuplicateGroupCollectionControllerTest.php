<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\Locale;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateGroupCollectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $groupCollection = GroupCollection::factory()->public()->create();

        $this->postJson("/api/group-collections/{$groupCollection->id}/duplicate")->assertUnauthorized();
    }

    public function test_copies_the_collection_and_the_groups_of_others_with_their_words_and_keeps_own_groups(): void
    {
        $user = User::factory()->create();
        $othersGroup = WordGroup::factory()->public()->openToContributions()->inLanguage(Locale::Fr)->create(['name' => 'Animaux']);
        Word::factory()->for($othersGroup)->create(['name' => 'chat']);
        $ownGroup = WordGroup::factory()->private()->createdBy($user)->inLanguage(Locale::Fr)->create();
        $groupCollection = GroupCollection::factory()->public()->openToContributions()->inLanguage(Locale::Fr)
            ->hasAttached($othersGroup, ['added_by' => $othersGroup->created_by])
            ->hasAttached($ownGroup, ['added_by' => $user->id])
            ->create(['name' => 'Jeux']);

        $response = $this->actingAs($user)->postJson("/api/group-collections/{$groupCollection->id}/duplicate");

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Jeux (copie)')
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.accepts_contributions', false)
            ->assertJsonMissingPath('data.hidden_word_groups_count');
        $copy = GroupCollection::findOrFail($response->json('data.id'));
        $this->assertTrue($copy->isCreatedBy($user));
        $copiedGroups = $copy->wordGroups()->get();
        $this->assertCount(2, $copiedGroups);
        $this->assertTrue($copiedGroups->contains($ownGroup));
        $this->assertFalse($copiedGroups->contains($othersGroup));
        $this->assertSame([$user->id], $copiedGroups->pluck('pivot.added_by')->unique()->values()->all());
        $copiedGroup = $copiedGroups->firstWhere('name', 'Animaux (copie)');
        $this->assertTrue($copiedGroup->isCreatedBy($user));
        $this->assertTrue($copiedGroup->isPrivate());
        $this->assertFalse($copiedGroup->accepts_contributions);
        $this->assertSame(['chat'], $copiedGroup->words()->pluck('name')->all());
        $this->assertSame([$user->id], $copiedGroup->words()->pluck('created_by')->all());
        $this->assertSame(1, WordGroup::where('created_by', $user->id)->where('name', 'like', '%(copie)')->count());
    }

    public function test_does_not_copy_hidden_groups_and_reports_how_many_they_are(): void
    {
        $user = User::factory()->create();
        $publicGroup = WordGroup::factory()->public()->create();
        $groupCollection = GroupCollection::factory()->public()
            ->hasAttached($publicGroup)
            ->hasAttached(WordGroup::factory()->private()->openToContributions())
            ->create();

        $response = $this->actingAs($user)->postJson("/api/group-collections/{$groupCollection->id}/duplicate");

        $response->assertCreated()->assertJsonMissingPath('data.hidden_word_groups_count');
        $this->assertSame(1, GroupCollection::findOrFail($response->json('data.id'))->wordGroups()->count());
    }

    public function test_member_of_the_collection_does_not_copy_the_private_groups_of_its_creator_they_were_not_given(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $privateGroup = WordGroup::factory()->private()->openToContributions()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->private()->openToContributions()->createdBy($creator)->hasAttached($privateGroup)->create();
        $groupCollection->members()->attach($member);

        $response = $this->actingAs($member)->postJson("/api/group-collections/{$groupCollection->id}/duplicate");

        $response->assertCreated()->assertJsonMissingPath('data.hidden_word_groups_count');
        $this->assertFalse(GroupCollection::findOrFail($response->json('data.id'))->wordGroups()->exists());
    }

    public function test_links_the_visible_groups_without_copying_them_when_asked(): void
    {
        $user = User::factory()->create();
        $othersGroup = WordGroup::factory()->public()->create();
        $ownGroup = WordGroup::factory()->private()->openToContributions()->createdBy($user)->create();
        $groupCollection = GroupCollection::factory()->public()
            ->hasAttached($othersGroup)
            ->hasAttached($ownGroup)
            ->hasAttached(WordGroup::factory()->private()->openToContributions())
            ->create();

        $response = $this->actingAs($user)->postJson("/api/group-collections/{$groupCollection->id}/duplicate", [
            'copy_word_groups' => false,
        ]);

        $response->assertCreated()->assertJsonMissingPath('data.hidden_word_groups_count');
        $linkedGroups = GroupCollection::findOrFail($response->json('data.id'))->wordGroups()->get();
        $this->assertEqualsCanonicalizing([$othersGroup->id, $ownGroup->id], $linkedGroups->modelKeys());
        $this->assertSame([$user->id], $linkedGroups->pluck('pivot.added_by')->unique()->values()->all());
        $this->assertDatabaseCount('word_groups', 3);
    }

    public function test_rejects_a_copy_word_groups_option_that_is_not_a_boolean(): void
    {
        $groupCollection = GroupCollection::factory()->public()->create();

        $this->actingAs(User::factory()->create())
            ->postJson("/api/group-collections/{$groupCollection->id}/duplicate", ['copy_word_groups' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('copy_word_groups');
    }

    public function test_does_not_copy_members_favorites_or_share_link(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $groupCollection = GroupCollection::factory()->private()->openToContributions()->createdBy($creator)
            ->hasAttached(WordGroup::factory()->public())
            ->create();
        $groupCollection->members()->attach($member);
        $groupCollection->favoritedBy()->attach($member);
        $groupCollection->regenerateShareToken();

        $response = $this->actingAs($member)->postJson("/api/group-collections/{$groupCollection->id}/duplicate");

        $response->assertCreated()->assertJsonPath('data.is_favorite', false);
        $copy = GroupCollection::findOrFail($response->json('data.id'));
        $this->assertSame(0, $copy->members()->count());
        $this->assertSame(0, $copy->favoritedBy()->count());
        $this->assertNull($copy->share_token);
    }

    public function test_copies_an_empty_collection_when_no_group_is_visible(): void
    {
        $user = User::factory()->create();
        $groupCollection = GroupCollection::factory()->public()->hasAttached(WordGroup::factory()->private()->openToContributions())->create();

        $response = $this->actingAs($user)->postJson("/api/group-collections/{$groupCollection->id}/duplicate");

        $response->assertCreated()->assertJsonMissingPath('data.hidden_word_groups_count');
        $this->assertFalse(GroupCollection::findOrFail($response->json('data.id'))->wordGroups()->exists());
    }

    public function test_returns_404_for_private_collection_of_another_user(): void
    {
        $groupCollection = GroupCollection::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->postJson("/api/group-collections/{$groupCollection->id}/duplicate")
            ->assertNotFound();

        $this->assertDatabaseCount('group_collections', 1);
    }
}
