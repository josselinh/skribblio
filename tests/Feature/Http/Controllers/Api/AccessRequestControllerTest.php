<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\ShareType;
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
        $accessRequest = WordGroupAccessRequest::factory()
            ->for(WordGroup::factory()->private()->openToContributions()->createdBy($creator), 'item')
            ->by(User::factory()->wantsEmails()->create())
            ->create();

        $this->actingAs($creator)->postJson("/api/access-requests/word-groups/{$accessRequest->id}/accept")->assertNoContent();

        $this->assertTrue($accessRequest->item->fresh()->isVisibleTo($accessRequest->requester));
        $this->assertTrue($accessRequest->fresh()->isAccepted());
        Notification::assertSentTo($accessRequest->requester, AccessRequestDecided::class);
    }

    public function test_refuse_keeps_item_hidden_and_notifies_requester(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $accessRequest = GroupCollectionAccessRequest::factory()
            ->for(GroupCollection::factory()->private()->createdBy($creator), 'item')
            ->by(User::factory()->wantsEmails()->create())
            ->create();

        $this->actingAs($creator)->postJson("/api/access-requests/group-collections/{$accessRequest->id}/refuse")->assertNoContent();

        $this->assertFalse($accessRequest->item->fresh()->isVisibleTo($accessRequest->requester));
        $this->assertSame('refused', $accessRequest->fresh()->status->value);
        Notification::assertSentTo($accessRequest->requester, AccessRequestDecided::class);
    }

    public function test_decision_sends_no_email_to_a_requester_who_did_not_turn_them_on(): void
    {
        Notification::fake();
        $requester = User::factory()->create();
        $accessRequest = WordGroupAccessRequest::factory()
            ->for(WordGroup::factory()->private()->openToContributions(), 'item')
            ->by($requester)
            ->create();

        $this->actingAs($accessRequest->item->creator)
            ->postJson("/api/access-requests/word-groups/{$accessRequest->id}/accept")
            ->assertNoContent();

        Notification::assertNothingSentTo($requester);
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

    public function test_accept_grants_the_chosen_type_of_sharing_partial_by_default(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->openToContributions()->createdBy($creator)->create();
        $fullRequest = WordGroupAccessRequest::factory()->for($wordGroup, 'item')->create();
        $partialRequest = WordGroupAccessRequest::factory()->for($wordGroup, 'item')->create();

        $this->actingAs($creator)->postJson("/api/access-requests/word-groups/{$fullRequest->id}/accept", ['share_type' => 'full'])->assertNoContent();
        $this->actingAs($creator)->postJson("/api/access-requests/word-groups/{$partialRequest->id}/accept")->assertNoContent();

        $this->assertSame(ShareType::Full, $wordGroup->fresh()->shareTypeOf($fullRequest->requester));
        $this->assertSame(ShareType::Partial, $wordGroup->fresh()->shareTypeOf($partialRequest->requester));
    }

    public function test_accept_with_an_unknown_type_of_sharing_returns_422(): void
    {
        $creator = User::factory()->create();
        $accessRequest = GroupCollectionAccessRequest::factory()
            ->for(GroupCollection::factory()->private()->createdBy($creator), 'item')
            ->create();

        $this->actingAs($creator)
            ->postJson("/api/access-requests/group-collections/{$accessRequest->id}/accept", ['share_type' => 'owner'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('share_type');

        $this->assertTrue($accessRequest->fresh()->isPending());
    }

    public function test_accept_returns_422_while_the_item_is_closed_to_contributions_but_refusing_works(): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->closedToContributions()->createdBy($creator)->create();
        $acceptedRequest = WordGroupAccessRequest::factory()->for($wordGroup, 'item')->create();
        $refusedRequest = WordGroupAccessRequest::factory()->for($wordGroup, 'item')->create();

        $this->actingAs($creator)
            ->postJson("/api/access-requests/word-groups/{$acceptedRequest->id}/accept")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => __('access_requests.not_shareable.word_group')]);
        $this->actingAs($creator)->postJson("/api/access-requests/word-groups/{$refusedRequest->id}/refuse")->assertNoContent();

        $this->assertTrue($acceptedRequest->fresh()->isPending());
        $this->assertFalse($wordGroup->members()->exists());
    }
}
