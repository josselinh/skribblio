<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\GroupCollection;
use App\Models\GroupCollectionAccessRequest;
use App\Models\User;
use App\Models\WordGroup;
use App\Models\WordGroupAccessRequest;
use App\Notifications\AccessRequestDecided;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccessRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_received_lists_pending_requests_on_own_groups_and_collections_most_recent_first(): void
    {
        $creator = User::factory()->create();
        $olderRequest = WordGroupAccessRequest::factory()
            ->for(WordGroup::factory()->private()->createdBy($creator), 'item')
            ->create(['created_at' => now()->subDay()]);
        $newerRequest = GroupCollectionAccessRequest::factory()
            ->for(GroupCollection::factory()->private()->createdBy($creator), 'item')
            ->create();
        WordGroupAccessRequest::factory()->for(WordGroup::factory()->private()->createdBy($creator), 'item')->refused()->create();
        WordGroupAccessRequest::factory()->create();

        $this->actingAs($creator)
            ->getJson('/api/access-requests/received')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$newerRequest->id, $olderRequest->id])
            ->assertJsonPath('data.*.kind', ['group-collections', 'word-groups']);
    }

    public function test_accept_grants_access_and_notifies_requester(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $accessRequest = WordGroupAccessRequest::factory()->for(WordGroup::factory()->private()->createdBy($creator), 'item')->create();

        $this->actingAs($creator)->postJson("/api/access-requests/word-groups/{$accessRequest->id}/accept")->assertNoContent();

        $this->assertTrue($accessRequest->item->fresh()->isVisibleTo($accessRequest->requester));
        $this->assertTrue($accessRequest->fresh()->isAccepted());
        Notification::assertSentTo($accessRequest->requester, AccessRequestDecided::class);
    }

    public function test_refuse_keeps_item_hidden_and_notifies_requester(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $accessRequest = GroupCollectionAccessRequest::factory()->for(GroupCollection::factory()->private()->createdBy($creator), 'item')->create();

        $this->actingAs($creator)->postJson("/api/access-requests/group-collections/{$accessRequest->id}/refuse")->assertNoContent();

        $this->assertFalse($accessRequest->item->fresh()->isVisibleTo($accessRequest->requester));
        $this->assertSame('refused', $accessRequest->fresh()->status->value);
        Notification::assertSentTo($accessRequest->requester, AccessRequestDecided::class);
    }

    public function test_decision_returns_403_for_another_user(): void
    {
        $accessRequest = WordGroupAccessRequest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->postJson("/api/access-requests/word-groups/{$accessRequest->id}/accept")
            ->assertForbidden();

        $this->assertTrue($accessRequest->fresh()->isPending());
    }

    public function test_decision_on_already_decided_request_returns_422(): void
    {
        $creator = User::factory()->create();
        $accessRequest = WordGroupAccessRequest::factory()->for(WordGroup::factory()->private()->createdBy($creator), 'item')->refused()->create();

        $this->actingAs($creator)
            ->postJson("/api/access-requests/word-groups/{$accessRequest->id}/accept")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Cette demande a déjà été traitée.']);
    }

    public function test_refusal_of_a_request_sent_from_a_collection_does_not_disclose_the_group_name(): void
    {
        $accessRequest = WordGroupAccessRequest::factory()
            ->for(WordGroup::factory()->private()->state(['name' => 'Secret']), 'item')
            ->refused()
            ->create(['via_group_collection_id' => GroupCollection::factory()->public()->create()->id]);

        $mail = (new AccessRequestDecided($accessRequest))->toMail($accessRequest->requester);

        $this->assertStringNotContainsString('Secret', implode(' ', [$mail->subject, ...$mail->introLines]));
    }
}
