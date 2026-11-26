<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_the_name_without_the_email(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);

        $this->actingAs(User::factory()->create())
            ->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertExactJson(['data' => ['id' => $user->id, 'name' => 'Alice']]);
    }

    public function test_returns_404_for_unknown_user(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/users/not-a-uuid')->assertNotFound();
    }

    public function test_word_groups_lists_the_users_public_groups_and_private_groups_shared_with_the_viewer(): void
    {
        $viewer = User::factory()->create();
        $alice = User::factory()->create();
        $publicGroup = WordGroup::factory()->public()->createdBy($alice)->create(['updated_at' => now()]);
        $sharedGroup = WordGroup::factory()->private()->openToContributions()->createdBy($alice)->create(['updated_at' => now()->subDay()]);
        $sharedGroup->members()->attach($viewer);
        $groupHiddenInASharedCollection = WordGroup::factory()->private()->openToContributions()->createdBy($alice)->create(['updated_at' => now()->subDays(2)]);
        GroupCollection::factory()->private()->openToContributions()->createdBy($alice)->hasAttached($groupHiddenInASharedCollection)->create()
            ->members()->attach($viewer);
        WordGroup::factory()->private()->openToContributions()->createdBy($alice)->create();
        WordGroup::factory()->public()->create();

        $this->actingAs($viewer)
            ->getJson("/api/users/{$alice->id}/word-groups")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$publicGroup->id, $sharedGroup->id])
            ->assertJsonPath('data.0.words_count', 0)
            ->assertJsonPath('data.0.is_favorite', false);
    }

    public function test_group_collections_lists_the_users_public_and_shared_collections(): void
    {
        $viewer = User::factory()->create();
        $alice = User::factory()->create();
        $publicCollection = GroupCollection::factory()->public()->createdBy($alice)->create(['updated_at' => now()]);
        $sharedCollection = GroupCollection::factory()->private()->openToContributions()->createdBy($alice)->create(['updated_at' => now()->subDay()]);
        $sharedCollection->members()->attach($viewer);
        GroupCollection::factory()->private()->openToContributions()->createdBy($alice)->create();

        $this->actingAs($viewer)
            ->getJson("/api/users/{$alice->id}/group-collections")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$publicCollection->id, $sharedCollection->id]);
    }

    public function test_own_page_lists_only_public_items_as_the_others_see_them(): void
    {
        $alice = User::factory()->create();
        $publicGroup = WordGroup::factory()->public()->createdBy($alice)->create();
        WordGroup::factory()->private()->createdBy($alice)->create();
        $publicCollection = GroupCollection::factory()->public()->createdBy($alice)->create();
        GroupCollection::factory()->private()->createdBy($alice)->create();

        $this->actingAs($alice)
            ->getJson("/api/users/{$alice->id}/word-groups")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$publicGroup->id])
            ->assertJsonPath('data.0.is_favorite', false);

        $this->actingAs($alice)
            ->getJson("/api/users/{$alice->id}/group-collections")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$publicCollection->id]);
    }
}
