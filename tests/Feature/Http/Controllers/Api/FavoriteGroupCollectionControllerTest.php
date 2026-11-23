<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\EmailNotification;
use App\Enums\Visibility;
use App\Models\GroupCollection;
use App\Models\User;
use App\Notifications\ItemFavorited;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FavoriteGroupCollectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $this->getJson('/api/favorites/group-collections')->assertUnauthorized();
    }

    public function test_update_adds_public_item_of_another_user_to_favorites_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $item = GroupCollection::factory()->public()->create();

        $this->actingAs($user)->putJson("/api/group-collections/{$item->id}/favorite")->assertNoContent();
        $this->actingAs($user)->putJson("/api/group-collections/{$item->id}/favorite")->assertNoContent();

        $this->assertSame(1, $user->favoriteGroupCollections()->count());
    }

    public function test_update_notifies_the_creator_who_turned_on_collection_emails(): void
    {
        Notification::fake();
        $creator = User::factory()->create(['email_notifications' => [EmailNotification::GroupCollectionFavorited->value => true]]);
        $user = User::factory()->create();
        $item = GroupCollection::factory()->public()->createdBy($creator)->create();

        $this->actingAs($user)->putJson("/api/group-collections/{$item->id}/favorite")->assertNoContent();

        Notification::assertSentTo(
            $creator,
            ItemFavorited::class,
            fn (ItemFavorited $notification): bool => $notification->toMail($creator)->subject
                === "{$user->name} a ajouté votre collection « {$item->name} » à ses favoris",
        );
    }

    public function test_update_sends_no_email_to_a_creator_who_only_turned_on_group_emails(): void
    {
        Notification::fake();
        $creator = User::factory()->create(['email_notifications' => [EmailNotification::WordGroupFavorited->value => true]]);
        $item = GroupCollection::factory()->public()->createdBy($creator)->create();

        $this->actingAs(User::factory()->create())->putJson("/api/group-collections/{$item->id}/favorite")->assertNoContent();

        Notification::assertNothingSentTo($creator);
    }

    public function test_update_returns_404_for_private_item_of_another_user(): void
    {
        $user = User::factory()->create();
        $item = GroupCollection::factory()->private()->create();

        $this->actingAs($user)->putJson("/api/group-collections/{$item->id}/favorite")->assertNotFound();

        $this->assertSame(0, $user->favoriteGroupCollections()->count());
    }

    public function test_destroy_removes_favorite_even_when_item_became_private(): void
    {
        $user = User::factory()->create();
        $item = GroupCollection::factory()->private()->create();
        $user->favoriteGroupCollections()->attach($item);

        $this->actingAs($user)->deleteJson("/api/group-collections/{$item->id}/favorite")->assertNoContent();

        $this->assertSame(0, $user->favoriteGroupCollections()->count());
    }

    public function test_index_lists_favorites_most_recently_added_first_and_flags_items_that_became_private(): void
    {
        $user = User::factory()->create();
        $olderFavorite = GroupCollection::factory()->public()->create();
        $newerFavorite = GroupCollection::factory()->public()->create();
        GroupCollection::factory()->public()->create();
        $user->favoriteGroupCollections()->attach($olderFavorite, ['created_at' => now()->subDay()]);
        $user->favoriteGroupCollections()->attach($newerFavorite, ['created_at' => now()]);
        $olderFavorite->update(['visibility' => Visibility::Private]);

        $response = $this->actingAs($user)->getJson('/api/favorites/group-collections');

        $response->assertOk()
            ->assertJsonPath('data.*.id', [$newerFavorite->id, $olderFavorite->id])
            ->assertJsonPath('data.*.is_accessible', [true, false])
            ->assertJsonPath('data.*.is_favorite', [true, true]);
    }

    public function test_own_list_flags_favorites(): void
    {
        $user = User::factory()->create();
        $favorite = GroupCollection::factory()->createdBy($user)->create(['updated_at' => now()]);
        GroupCollection::factory()->createdBy($user)->create(['updated_at' => now()->subDay()]);
        $user->favoriteGroupCollections()->attach($favorite);

        $this->actingAs($user)
            ->getJson('/api/group-collections')
            ->assertJsonPath('data.*.is_favorite', [true, false]);
    }

    public function test_show_flags_favorite(): void
    {
        $user = User::factory()->create();
        $item = GroupCollection::factory()->createdBy($user)->create();
        $user->favoriteGroupCollections()->attach($item);

        $this->actingAs($user)->getJson("/api/group-collections/{$item->id}")->assertJsonPath('data.is_favorite', true);
    }
}
