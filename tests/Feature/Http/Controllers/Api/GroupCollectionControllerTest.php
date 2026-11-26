<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\Visibility;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GroupCollectionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['getJson'])]
    #[TestWith(['postJson'])]
    public function test_returns_401_for_guest(string $method): void
    {
        $this->{$method}('/api/group-collections')->assertUnauthorized();
    }

    public function test_index_lists_only_collections_created_by_current_user_most_recently_updated_first(): void
    {
        $user = User::factory()->create();
        $olderCollection = GroupCollection::factory()->createdBy($user)->create(['updated_at' => now()->subDay()]);
        $newerCollection = GroupCollection::factory()->createdBy($user)->public()->create(['updated_at' => now()]);
        GroupCollection::factory()->public()->create();

        $response = $this->actingAs($user)->getJson('/api/group-collections');

        $response->assertOk()
            ->assertJsonPath('data.*.id', [$newerCollection->id, $olderCollection->id])
            ->assertJsonPath('data.0', [
                'id' => $newerCollection->id,
                'name' => $newerCollection->name,
                'language' => 'fr',
                'visibility' => 'public',
                'accepts_contributions' => false,
                'contributions_members_only' => false,
                'creator' => ['id' => $user->id, 'name' => $user->name],
                'word_groups_count' => 1,
                'is_favorite' => false,
                'created_at' => $newerCollection->created_at->toIso8601String(),
                'updated_at' => $newerCollection->updated_at->toIso8601String(),
                'can' => ['update' => true, 'delete' => true, 'share' => false, 'manage_access' => true, 'leave' => false, 'add_word_group' => true, 'manage_contributions' => true, 'duplicate' => true],
            ]);
    }

    public function test_index_paginates_by_twelve(): void
    {
        $user = User::factory()->create();
        GroupCollection::factory()->count(13)->createdBy($user)->create();

        $this->actingAs($user)
            ->getJson('/api/group-collections?page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 13);
    }

    public function test_store_creates_an_empty_collection_owned_by_the_current_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/group-collections', [
            'name' => 'Soirée jeux',
            'visibility' => 'public',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Soirée jeux')
            ->assertJsonPath('data.accepts_contributions', false);
        $groupCollection = GroupCollection::findOrFail($response->json('data.id'));
        $this->assertTrue($groupCollection->isCreatedBy($user));
        $this->assertFalse($groupCollection->wordGroups()->exists());
    }

    public function test_store_with_empty_payload_returns_422(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/group-collections', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'visibility']);
    }

    public function test_show_returns_collection_with_all_groups_for_its_creator(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)
            ->hasAttached([
                WordGroup::factory()->private()->createdBy($creator)->create(['name' => 'B']),
                WordGroup::factory()->public()->create(['name' => 'A']),
            ])
            ->create();

        $response = $this->actingAs($creator)->getJson("/api/group-collections/{$groupCollection->id}");

        $response->assertOk()
            ->assertJsonPath('data.word_groups.*.name', ['A', 'B'])
            ->assertJsonMissingPath('data.hidden_word_groups_count')
            ->assertJsonPath('data.can.update', true);
    }

    public function test_show_hides_private_groups_from_other_users_and_counts_them(): void
    {
        $creator = User::factory()->create();
        $publicGroup = WordGroup::factory()->public()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->public()->createdBy($creator)
            ->hasAttached([$publicGroup, WordGroup::factory()->private()->openToContributions()->createdBy($creator)->create()])
            ->create();

        $response = $this->actingAs(User::factory()->create())->getJson("/api/group-collections/{$groupCollection->id}");

        $response->assertOk()
            ->assertJsonPath('data.word_groups.*.id', [$publicGroup->id])
            ->assertJsonMissingPath('data.hidden_word_groups_count')
            ->assertJsonPath('data.creator', ['id' => $creator->id, 'name' => $creator->name])
            ->assertJsonPath('data.can.update', false);
    }

    public function test_show_returns_public_collection_to_guest_with_its_public_groups_only(): void
    {
        $publicGroup = WordGroup::factory()->public()->create();
        $groupCollection = GroupCollection::factory()->public()
            ->hasAttached([$publicGroup, WordGroup::factory()->private()->openToContributions()->create()])
            ->create();

        $this->getJson("/api/group-collections/{$groupCollection->id}")
            ->assertOk()
            ->assertJsonPath('data.word_groups.*.id', [$publicGroup->id])
            ->assertJsonMissingPath('data.hidden_word_groups_count')
            ->assertJsonMissingPath('data.creator')
            ->assertJsonMissingPath('data.word_groups.0.creator')
            ->assertJsonMissingPath('data.word_groups.0.added_by')
            ->assertJsonPath('data.can.duplicate', false);
    }

    public function test_show_returns_404_to_guest_for_private_collection(): void
    {
        $groupCollection = GroupCollection::factory()->private()->create();

        $this->getJson("/api/group-collections/{$groupCollection->id}")->assertNotFound();
    }

    public function test_show_returns_404_for_private_collection_of_another_user(): void
    {
        $groupCollection = GroupCollection::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->getJson("/api/group-collections/{$groupCollection->id}")
            ->assertNotFound();
    }

    public function test_update_renames_collection_and_changes_visibility_for_its_creator(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->private()->createdBy($creator)->create();

        $this->actingAs($creator)
            ->patchJson("/api/group-collections/{$groupCollection->id}", ['name' => 'Soirée jeux', 'visibility' => 'public'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Soirée jeux');

        $this->assertSame(Visibility::Public, $groupCollection->fresh()->visibility);
    }

    public function test_update_returns_403_for_another_user_on_public_collection(): void
    {
        $groupCollection = GroupCollection::factory()->public()->create(['name' => 'Soirée jeux']);

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/group-collections/{$groupCollection->id}", ['name' => 'Piraté', 'visibility' => 'public'])
            ->assertForbidden();

        $this->assertSame('Soirée jeux', $groupCollection->fresh()->name);
    }

    public function test_update_with_empty_payload_returns_422(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)->create();

        $this->actingAs($creator)
            ->patchJson("/api/group-collections/{$groupCollection->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'visibility']);
    }

    public function test_store_and_update_set_whether_the_collection_accepts_contributions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/group-collections', [
            'name' => 'Soirée jeux',
            'visibility' => 'public',
            'accepts_contributions' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.accepts_contributions', true);
        $groupCollection = GroupCollection::findOrFail($response->json('data.id'));

        $this->actingAs($user)
            ->patchJson("/api/group-collections/{$groupCollection->id}", ['name' => 'Soirée jeux', 'visibility' => 'public', 'accepts_contributions' => false])
            ->assertOk()
            ->assertJsonPath('data.accepts_contributions', false);
        $this->assertFalse($groupCollection->fresh()->accepts_contributions);
    }

    public function test_show_tells_a_contributor_which_groups_they_can_remove(): void
    {
        $creator = User::factory()->create();
        $contributor = User::factory()->create();
        $creatorsGroup = WordGroup::factory()->public()->createdBy($creator)->create(['name' => 'A']);
        $contributorsGroup = WordGroup::factory()->public()->createdBy($contributor)->create(['name' => 'B']);
        $groupCollection = GroupCollection::factory()->public()->openToContributions()->createdBy($creator)
            ->hasAttached($creatorsGroup, ['added_by' => $creator->id])
            ->hasAttached($contributorsGroup, ['added_by' => $contributor->id])
            ->create();

        $this->actingAs($contributor)
            ->getJson("/api/group-collections/{$groupCollection->id}")
            ->assertOk()
            ->assertJsonPath('data.can.add_word_group', true)
            ->assertJsonPath('data.word_groups.*.can.remove_from_collection', [false, true]);
    }

    public function test_show_tells_who_added_each_group_and_null_once_they_were_deleted(): void
    {
        $creator = User::factory()->create();
        $contributor = User::factory()->create();
        $deletedContributor = User::factory()->create();
        $groupCollection = GroupCollection::factory()->public()->openToContributions()->createdBy($creator)
            ->hasAttached(WordGroup::factory()->public()->create(['name' => 'A']), ['added_by' => $creator->id])
            ->hasAttached(WordGroup::factory()->public()->create(['name' => 'B']), ['added_by' => $contributor->id])
            ->hasAttached(WordGroup::factory()->public()->create(['name' => 'C']), ['added_by' => $deletedContributor->id])
            ->create();
        $deletedContributor->delete();

        $this->actingAs(User::factory()->create())
            ->getJson("/api/group-collections/{$groupCollection->id}")
            ->assertOk()
            ->assertJsonPath('data.word_groups.0.added_by', ['id' => $creator->id, 'name' => $creator->name])
            ->assertJsonPath('data.word_groups.1.added_by', ['id' => $contributor->id, 'name' => $contributor->name])
            ->assertJsonPath('data.word_groups.2.added_by', null)
            ->assertJsonPath('data.word_groups.0.added_at', fn (?string $addedAt): bool => $addedAt !== null);
    }

    public function test_destroy_deletes_collection_and_keeps_its_groups(): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)->hasAttached($wordGroup)->create();

        $this->actingAs($creator)
            ->deleteJson("/api/group-collections/{$groupCollection->id}")
            ->assertNoContent();

        $this->assertModelMissing($groupCollection);
        $this->assertModelExists($wordGroup);
    }

    public function test_destroy_returns_403_for_another_user_on_public_collection(): void
    {
        $groupCollection = GroupCollection::factory()->public()->openToContributions()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/group-collections/{$groupCollection->id}")
            ->assertForbidden();

        $this->assertModelExists($groupCollection);
    }

    public function test_destroy_returns_404_for_private_collection_of_another_user(): void
    {
        $groupCollection = GroupCollection::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/group-collections/{$groupCollection->id}")
            ->assertNotFound();

        $this->assertModelExists($groupCollection);
    }
}
