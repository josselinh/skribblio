<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use App\Models\WordGroup;
use App\Models\WordGroupAccessRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordGroupSharingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_link_is_generated_once_then_reused_without_touching_the_group(): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create(['updated_at' => now()->subWeek()]);

        $firstUrl = $this->actingAs($creator)->getJson("/api/word-groups/{$wordGroup->id}/share-link")->assertOk()->json('data.url');
        $secondUrl = $this->actingAs($creator)->getJson("/api/word-groups/{$wordGroup->id}/share-link")->json('data.url');

        $this->assertSame($firstUrl, $secondUrl);
        $this->assertSame(url('/share/groups/'.$wordGroup->fresh()->share_token), $firstUrl);
        $this->assertFalse($wordGroup->fresh()->updated_at->isToday());
    }

    public function test_regenerating_share_link_replaces_the_token(): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create();
        $oldToken = $wordGroup->ensureShareToken();

        $this->actingAs($creator)->postJson("/api/word-groups/{$wordGroup->id}/share-link")->assertOk();

        $this->assertNotSame($oldToken, $wordGroup->fresh()->share_token);
    }

    public function test_share_link_returns_403_for_public_group_or_another_user(): void
    {
        $creator = User::factory()->create();
        $publicGroup = WordGroup::factory()->public()->createdBy($creator)->create();
        $privateGroup = WordGroup::factory()->private()->createdBy($creator)->create();
        $privateGroup->members()->attach($member = User::factory()->create());

        $this->actingAs($creator)->getJson("/api/word-groups/{$publicGroup->id}/share-link")->assertForbidden();
        $this->actingAs($member)->getJson("/api/word-groups/{$privateGroup->id}/share-link")->assertForbidden();
    }

    public function test_share_token_is_never_exposed_in_group_responses(): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create();
        $wordGroup->ensureShareToken();

        $this->actingAs($creator)->getJson("/api/word-groups/{$wordGroup->id}")->assertJsonMissingPath('data.share_token');
    }

    public function test_access_lists_pending_requests_and_members_for_the_creator_only(): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create();
        $requester = User::factory()->create(['name' => 'Bob']);
        WordGroupAccessRequest::factory()->for($wordGroup, 'item')->by($requester)->create();
        WordGroupAccessRequest::factory()->for($wordGroup, 'item')->refused()->create();
        $wordGroup->members()->attach($member = User::factory()->create(['name' => 'Carla']));

        $this->actingAs($creator)
            ->getJson("/api/word-groups/{$wordGroup->id}/access")
            ->assertOk()
            ->assertJsonPath('data.pending_requests.*.requester.name', ['Bob'])
            ->assertJsonPath('data.members.*.name', ['Carla']);

        $this->actingAs($member)->getJson("/api/word-groups/{$wordGroup->id}/access")->assertForbidden();
    }

    public function test_revoke_removes_member_access_and_request(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create();
        $wordGroup->members()->attach($member);
        WordGroupAccessRequest::factory()->for($wordGroup, 'item')->by($member)->create(['status' => 'accepted']);

        $this->actingAs($creator)->deleteJson("/api/word-groups/{$wordGroup->id}/members/{$member->id}")->assertNoContent();

        $this->assertFalse($wordGroup->fresh()->isVisibleTo($member));
        $this->assertDatabaseMissing('word_group_access_requests', ['word_group_id' => $wordGroup->id, 'user_id' => $member->id]);
    }
}
