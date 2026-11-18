<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\Visibility;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteWordGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $this->getJson('/api/favorites/word-groups')->assertUnauthorized();
    }

    public function test_update_adds_public_item_of_another_user_to_favorites_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $item = WordGroup::factory()->public()->create();

        $this->actingAs($user)->putJson("/api/word-groups/{$item->id}/favorite")->assertNoContent();
        $this->actingAs($user)->putJson("/api/word-groups/{$item->id}/favorite")->assertNoContent();

        $this->assertSame(1, $user->favoriteWordGroups()->count());
    }

    public function test_update_returns_404_for_private_item_of_another_user(): void
    {
        $user = User::factory()->create();
        $item = WordGroup::factory()->private()->create();

        $this->actingAs($user)->putJson("/api/word-groups/{$item->id}/favorite")->assertNotFound();

        $this->assertSame(0, $user->favoriteWordGroups()->count());
    }

    public function test_destroy_removes_favorite_even_when_item_became_private(): void
    {
        $user = User::factory()->create();
        $item = WordGroup::factory()->private()->create();
        $user->favoriteWordGroups()->attach($item);

        $this->actingAs($user)->deleteJson("/api/word-groups/{$item->id}/favorite")->assertNoContent();

        $this->assertSame(0, $user->favoriteWordGroups()->count());
    }

    public function test_index_lists_favorites_most_recently_added_first_and_flags_items_that_became_private(): void
    {
        $user = User::factory()->create();
        $olderFavorite = WordGroup::factory()->public()->create();
        $newerFavorite = WordGroup::factory()->public()->create();
        WordGroup::factory()->public()->create();
        $user->favoriteWordGroups()->attach($olderFavorite, ['created_at' => now()->subDay()]);
        $user->favoriteWordGroups()->attach($newerFavorite, ['created_at' => now()]);
        $olderFavorite->update(['visibility' => Visibility::Private]);

        $response = $this->actingAs($user)->getJson('/api/favorites/word-groups');

        $response->assertOk()
            ->assertJsonPath('data.*.id', [$newerFavorite->id, $olderFavorite->id])
            ->assertJsonPath('data.*.is_accessible', [true, false])
            ->assertJsonPath('data.*.is_favorite', [true, true]);
    }

    public function test_own_list_flags_favorites(): void
    {
        $user = User::factory()->create();
        $favorite = WordGroup::factory()->createdBy($user)->create(['updated_at' => now()]);
        WordGroup::factory()->createdBy($user)->create(['updated_at' => now()->subDay()]);
        $user->favoriteWordGroups()->attach($favorite);

        $this->actingAs($user)
            ->getJson('/api/word-groups')
            ->assertJsonPath('data.*.is_favorite', [true, false]);
    }

    public function test_show_flags_favorite(): void
    {
        $user = User::factory()->create();
        $item = WordGroup::factory()->createdBy($user)->create();
        $user->favoriteWordGroups()->attach($item);

        $this->actingAs($user)->getJson("/api/word-groups/{$item->id}")->assertJsonPath('data.is_favorite', true);
    }
}
