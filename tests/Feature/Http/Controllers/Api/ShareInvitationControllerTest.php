<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use App\Models\WordGroupAccessRequest;
use App\Notifications\AccessRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ShareInvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_describes_the_shared_group_without_its_id_until_access_is_granted(): void
    {
        $creator = User::factory()->create(['name' => 'Alice']);
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create(['name' => 'Animaux']);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/share/word-groups/'.$wordGroup->ensureShareToken())
            ->assertOk()
            ->assertExactJson(['data' => [
                'kind' => 'word-groups',
                'name' => 'Animaux',
                'creator' => ['name' => 'Alice'],
                'purpose' => 'access',
                'status' => 'none',
            ]]);
    }

    public function test_show_gives_the_id_when_the_user_already_has_access(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();
        $wordGroup->members()->attach($member = User::factory()->create());

        $this->actingAs($member)
            ->getJson('/api/share/word-groups/'.$wordGroup->ensureShareToken())
            ->assertJsonPath('data.status', 'accessible')
            ->assertJsonPath('data.id', $wordGroup->id);
    }

    public function test_show_returns_404_for_unknown_or_regenerated_token(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->create();
        $oldToken = $wordGroup->ensureShareToken();
        $wordGroup->regenerateShareToken();

        $this->actingAs($user)->getJson('/api/share/word-groups/unknown-token')->assertNotFound();
        $this->actingAs($user)->getJson("/api/share/word-groups/{$oldToken}")->assertNotFound();
    }

    public function test_request_creates_pending_request_notifies_creator_once_and_is_idempotent(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $requester = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create();
        $token = $wordGroup->ensureShareToken();

        $this->actingAs($requester)->postJson("/api/share/word-groups/{$token}/access-request")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');
        $this->actingAs($requester)->postJson("/api/share/word-groups/{$token}/access-request");

        $this->assertSame(1, $wordGroup->accessRequests()->pending()->count());
        Notification::assertSentToTimes($creator, AccessRequested::class, 1);
    }

    public function test_request_after_refusal_sends_a_new_request(): void
    {
        Notification::fake();
        $requester = User::factory()->create();
        $request = WordGroupAccessRequest::factory()->refused()->by($requester)->create();
        $token = $request->item->ensureShareToken();

        $this->actingAs($requester)->getJson("/api/share/word-groups/{$token}")->assertJsonPath('data.status', 'refused');
        $this->actingAs($requester)->postJson("/api/share/word-groups/{$token}/access-request")->assertJsonPath('data.status', 'pending');

        Notification::assertSentTo($request->item->creator, AccessRequested::class);
    }

    public function test_request_on_own_or_visible_item_creates_no_request(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create();

        $this->actingAs($creator)
            ->postJson('/api/share/word-groups/'.$wordGroup->ensureShareToken().'/access-request')
            ->assertJsonPath('data.status', 'accessible');

        $this->assertSame(0, $wordGroup->accessRequests()->count());
        Notification::assertNothingSent();
    }

    public function test_collection_request_creates_pending_request(): void
    {
        Notification::fake();
        $groupCollection = GroupCollection::factory()->private()->create();

        $this->actingAs($requester = User::factory()->create())
            ->postJson('/api/share/group-collections/'.$groupCollection->ensureShareToken().'/access-request')
            ->assertJsonPath('data.kind', 'group-collections')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('group_collection_access_requests', ['user_id' => $requester->id, 'status' => 'pending']);
    }

    public function test_share_link_of_a_group_open_to_members_only_asks_to_contribute(): void
    {
        Notification::fake();
        $wordGroup = WordGroup::factory()->openToMembersOnly()->create();
        $token = $wordGroup->ensureShareToken();
        $requester = User::factory()->create();

        $this->actingAs($requester)->getJson("/api/share/word-groups/{$token}")
            ->assertJsonPath('data.id', $wordGroup->id)
            ->assertJsonPath('data.purpose', 'contribution')
            ->assertJsonPath('data.status', 'none');
        $this->actingAs($requester)->postJson("/api/share/word-groups/{$token}/access-request")
            ->assertJsonPath('data.status', 'pending');

        Notification::assertSentTo($wordGroup->creator, AccessRequested::class,
            fn (AccessRequested $notification): bool => str_contains($notification->toMail($wordGroup->creator)->subject, 'demande à contribuer'));

        $wordGroup->acceptAccessRequest($wordGroup->accessRequests()->sole());

        $this->actingAs($requester)->getJson("/api/share/word-groups/{$token}")->assertJsonPath('data.status', 'accessible');
        $this->assertTrue($requester->can('addWord', $wordGroup->fresh()));
    }
}
