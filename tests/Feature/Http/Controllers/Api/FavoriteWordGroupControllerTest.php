<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\EmailNotification;
use App\Enums\Visibility;
use App\Models\User;
use App\Models\WordGroup;
use App\Notifications\ItemFavorited;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    public function test_update_notifies_the_creator_once_even_when_favorited_again_after_removal(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $item = WordGroup::factory()->public()->createdBy(User::factory()->wantsEmails()->create())->create();

        $this->actingAs($user)->putJson("/api/word-groups/{$item->id}/favorite")->assertNoContent();
        $this->actingAs($user)->putJson("/api/word-groups/{$item->id}/favorite")->assertNoContent();
        $this->actingAs($user)->deleteJson("/api/word-groups/{$item->id}/favorite")->assertNoContent();
        $this->actingAs($user)->putJson("/api/word-groups/{$item->id}/favorite")->assertNoContent();

        Notification::assertSentToTimes($item->creator, ItemFavorited::class, 1);
        Notification::assertSentTo(
            $item->creator,
            ItemFavorited::class,
            fn (ItemFavorited $notification): bool => $notification->toMail($item->creator)->subject
                === "{$user->name} added your group “{$item->name}” to their favorites",
        );
    }

    public function test_update_does_not_notify_when_favoriting_own_item(): void
    {
        Notification::fake();
        $user = User::factory()->wantsEmails()->create();
        $item = WordGroup::factory()->private()->createdBy($user)->create();

        $this->actingAs($user)->putJson("/api/word-groups/{$item->id}/favorite")->assertNoContent();

        Notification::assertNothingSent();
    }

    public function test_update_sends_no_email_to_a_creator_who_did_not_turn_them_on(): void
    {
        Notification::fake();
        $creator = User::factory()->create(['email_notifications' => [EmailNotification::GroupCollectionFavorited->value => true]]);
        $item = WordGroup::factory()->public()->createdBy($creator)->create();

        $this->actingAs(User::factory()->create())->putJson("/api/word-groups/{$item->id}/favorite")->assertNoContent();

        Notification::assertNothingSentTo($creator);
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

    public function test_index_lists_favorites_most_recently_added_first_and_hides_items_that_became_private_without_removing_them(): void
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
            ->assertJsonPath('data.*.id', [$newerFavorite->id])
            ->assertJsonPath('data.*.is_favorite', [true]);
        $this->assertTrue($user->favoriteWordGroups()->whereKey($olderFavorite->id)->exists());
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
