<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use App\Notifications\AccessRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GroupCollectionSharingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_link_points_to_the_collection_share_page(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->private()->createdBy($creator)->create();

        $url = $this->actingAs($creator)->getJson("/api/group-collections/{$groupCollection->id}/share-link")->assertOk()->json('data.url');

        $this->assertSame(url('/share/collections/'.$groupCollection->fresh()->share_token), $url);
    }

    public function test_request_hidden_word_groups_sends_one_request_per_hidden_group_to_its_creator(): void
    {
        Notification::fake();
        $viewer = User::factory()->create();
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $alicesGroup = WordGroup::factory()->private()->createdBy($alice)->create();
        $bobsGroup = WordGroup::factory()->private()->createdBy($bob)->create();
        $groupCollection = GroupCollection::factory()->public()
            ->hasAttached([$alicesGroup, $bobsGroup, WordGroup::factory()->public()->create()])
            ->create();

        $this->actingAs($viewer)
            ->postJson("/api/group-collections/{$groupCollection->id}/hidden-word-groups/access-requests")
            ->assertOk()
            ->assertJsonPath('data.requested_count', 2);

        $this->assertDatabaseHas('word_group_access_requests', [
            'word_group_id' => $alicesGroup->id,
            'user_id' => $viewer->id,
            'status' => 'pending',
            'via_group_collection_id' => $groupCollection->id,
        ]);
        Notification::assertSentTo($alice, AccessRequested::class);
        Notification::assertSentTo($bob, AccessRequested::class);

        $this->actingAs($viewer)
            ->getJson("/api/group-collections/{$groupCollection->id}")
            ->assertJsonPath('data.hidden_word_groups_count', 2)
            ->assertJsonPath('data.hidden_word_groups_requested_count', 2);
    }

    public function test_request_hidden_word_groups_returns_404_for_private_collection_of_another_user(): void
    {
        $groupCollection = GroupCollection::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->postJson("/api/group-collections/{$groupCollection->id}/hidden-word-groups/access-requests")
            ->assertNotFound();
    }
}
