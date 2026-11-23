<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\Visibility;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WordGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['getJson'])]
    #[TestWith(['postJson'])]
    public function test_returns_401_for_guest(string $method): void
    {
        $this->{$method}('/api/word-groups')->assertUnauthorized();
    }

    #[TestWith(['patchJson'])]
    #[TestWith(['deleteJson'])]
    public function test_changing_a_public_group_returns_401_for_guest(string $method): void
    {
        $wordGroup = WordGroup::factory()->public()->create();

        $this->{$method}("/api/word-groups/{$wordGroup->id}")->assertUnauthorized();
    }

    public function test_show_returns_public_group_to_guest_without_creator_favorite_or_actions(): void
    {
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();

        $this->getJson("/api/word-groups/{$wordGroup->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $wordGroup->id)
            ->assertJsonPath('data.accepts_contributions', true)
            ->assertJsonMissingPath('data.creator')
            ->assertJsonMissingPath('data.is_favorite')
            ->assertJsonPath('data.can', [
                'update' => false,
                'delete' => false,
                'share' => false,
                'manage_access' => false,
                'leave' => false,
                'add_word' => false,
                'duplicate' => false,
            ]);
    }

    public function test_show_returns_404_to_guest_for_private_group(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->getJson("/api/word-groups/{$wordGroup->id}")->assertNotFound();
    }

    public function test_show_returns_404_to_unverified_creator_of_a_private_group(): void
    {
        $creator = User::factory()->unverified()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create();

        $this->actingAs($creator)->getJson("/api/word-groups/{$wordGroup->id}")->assertNotFound();
    }

    #[TestWith(['getJson'])]
    #[TestWith(['postJson'])]
    public function test_returns_403_for_unverified_user(string $method): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->{$method}('/api/word-groups')
            ->assertForbidden();
    }

    public function test_index_lists_only_groups_created_by_current_user_most_recently_updated_first(): void
    {
        $user = User::factory()->create();
        $olderGroup = WordGroup::factory()->createdBy($user)->create(['updated_at' => now()->subDay()]);
        $newerGroup = WordGroup::factory()->createdBy($user)->public()->create(['updated_at' => now()]);
        WordGroup::factory()->public()->create();

        $response = $this->actingAs($user)->getJson('/api/word-groups');

        $response->assertOk()
            ->assertJsonPath('data.*.id', [$newerGroup->id, $olderGroup->id])
            ->assertJsonPath('data.0', [
                'id' => $newerGroup->id,
                'name' => $newerGroup->name,
                'language' => 'fr',
                'visibility' => 'public',
                'accepts_contributions' => false,
                'contributions_members_only' => false,
                'is_favorite' => false,
                'created_at' => $newerGroup->created_at->toIso8601String(),
                'updated_at' => $newerGroup->updated_at->toIso8601String(),
                'can' => ['update' => true, 'delete' => true, 'share' => false, 'manage_access' => true, 'leave' => false, 'add_word' => true, 'duplicate' => true],
            ]);
    }

    public function test_index_paginates_by_twelve(): void
    {
        $user = User::factory()->create();
        WordGroup::factory()->count(13)->createdBy($user)->create();

        $response = $this->actingAs($user)->getJson('/api/word-groups?page=2');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 13);
    }

    public function test_store_creates_group_owned_by_current_user_and_returns_201(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/word-groups', [
            'name' => 'Animaux',
            'visibility' => 'private',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Animaux')
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.accepts_contributions', false);
        $this->assertDatabaseHas('word_groups', [
            'id' => $response->json('data.id'),
            'name' => 'Animaux',
            'visibility' => Visibility::Private->value,
            'created_by' => $user->id,
        ]);
    }

    public function test_store_with_empty_payload_returns_422_with_french_messages(): void
    {
        $response = $this->actingAs(User::factory()->create())->postJson('/api/word-groups', []);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'name' => 'Le champ nom est obligatoire.',
            'visibility' => 'Le champ visibilité est obligatoire.',
        ]);
    }

    public function test_store_rejects_unknown_visibility_with_422(): void
    {
        $response = $this->actingAs(User::factory()->create())->postJson('/api/word-groups', [
            'name' => 'Animaux',
            'visibility' => 'friends',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['visibility']);
        $this->assertDatabaseCount('word_groups', 0);
    }

    public function test_show_returns_public_group_of_another_user_with_restricted_abilities(): void
    {
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();

        $response = $this->actingAs(User::factory()->create())->getJson("/api/word-groups/{$wordGroup->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $wordGroup->id)
            ->assertJsonPath('data.creator', ['id' => $wordGroup->creator->id, 'name' => $wordGroup->creator->name])
            ->assertJsonPath('data.can', ['update' => false, 'delete' => false, 'share' => false, 'manage_access' => false, 'leave' => false, 'add_word' => true, 'duplicate' => true]);
    }

    public function test_show_returns_404_for_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->getJson("/api/word-groups/{$wordGroup->id}")
            ->assertNotFound();
    }

    public function test_show_returns_404_for_malformed_id(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/word-groups/not-a-uuid')
            ->assertNotFound();
    }

    public function test_update_renames_group_and_changes_visibility_for_its_creator(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($user)->create();

        $response = $this->actingAs($user)->patchJson("/api/word-groups/{$wordGroup->id}", [
            'name' => 'Animaux de la ferme',
            'visibility' => 'public',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Animaux de la ferme');
        $this->assertSame(Visibility::Public, $wordGroup->fresh()->visibility);
    }

    public function test_update_returns_403_for_another_user_on_public_group(): void
    {
        $wordGroup = WordGroup::factory()->public()->create(['name' => 'Animaux']);

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/word-groups/{$wordGroup->id}", ['name' => 'Piraté', 'visibility' => 'public'])
            ->assertForbidden();

        $this->assertSame('Animaux', $wordGroup->fresh()->name);
    }

    public function test_update_with_empty_payload_returns_422(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->patchJson("/api/word-groups/{$wordGroup->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'visibility']);
    }

    public function test_store_and_update_set_whether_the_group_accepts_contributions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/word-groups', [
            'name' => 'Animaux',
            'visibility' => 'public',
            'accepts_contributions' => false,
        ]);

        $response->assertCreated()->assertJsonPath('data.accepts_contributions', false);
        $wordGroup = WordGroup::findOrFail($response->json('data.id'));
        $this->assertFalse($wordGroup->accepts_contributions);

        $this->actingAs($user)
            ->patchJson("/api/word-groups/{$wordGroup->id}", ['name' => 'Animaux', 'visibility' => 'public', 'accepts_contributions' => true])
            ->assertOk()
            ->assertJsonPath('data.accepts_contributions', true);
        $this->assertTrue($wordGroup->fresh()->accepts_contributions);

        $this->actingAs($user)
            ->patchJson("/api/word-groups/{$wordGroup->id}", ['name' => 'Animaux', 'visibility' => 'public', 'contributions_members_only' => true])
            ->assertOk()
            ->assertJsonPath('data.contributions_members_only', true)
            ->assertJsonPath('data.can.share', true);
        $this->assertTrue($wordGroup->fresh()->contributions_members_only);
    }

    public function test_destroy_deletes_group_with_its_words_and_may_leave_a_collection_empty(): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->createdBy($creator)->create();
        $word = Word::factory()->for($wordGroup)->create();
        $collectionOfAnotherUser = GroupCollection::factory()->hasAttached($wordGroup)->create();

        $this->actingAs($creator)
            ->deleteJson("/api/word-groups/{$wordGroup->id}")
            ->assertNoContent();

        $this->assertModelMissing($wordGroup);
        $this->assertModelMissing($word);
        $this->assertModelExists($collectionOfAnotherUser);

        $this->actingAs($collectionOfAnotherUser->creator)
            ->getJson("/api/group-collections/{$collectionOfAnotherUser->id}")
            ->assertOk()
            ->assertJsonPath('data.word_groups', [])
            ->assertJsonPath('data.hidden_word_groups_count', 0);
    }

    public function test_destroy_returns_403_for_another_user_on_public_group(): void
    {
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/word-groups/{$wordGroup->id}")
            ->assertForbidden();

        $this->assertModelExists($wordGroup);
    }

    public function test_destroy_returns_404_for_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/word-groups/{$wordGroup->id}")
            ->assertNotFound();

        $this->assertModelExists($wordGroup);
    }
}
