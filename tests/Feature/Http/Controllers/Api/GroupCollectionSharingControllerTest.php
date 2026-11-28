<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\ShareType;
use App\Models\GroupCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupCollectionSharingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_link_points_to_the_collection_share_page(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->private()->openToContributions()->createdBy($creator)->create();

        $url = $this->actingAs($creator)->getJson("/api/group-collections/{$groupCollection->id}/share-link")->assertOk()->json('data.url');

        $this->assertSame(url('/share/collections/'.$groupCollection->fresh()->share_token), $url);
    }

    public function test_member_leaving_a_private_collection_no_longer_sees_it(): void
    {
        $member = User::factory()->create();
        $groupCollection = GroupCollection::factory()->private()->openToContributions()->create();
        $groupCollection->members()->attach($member);

        $this->actingAs($member)->deleteJson("/api/group-collections/{$groupCollection->id}/membership")->assertNoContent();

        $this->assertFalse($groupCollection->fresh()->isVisibleTo($member));
    }

    public function test_leaving_returns_403_for_a_viewer_who_is_not_a_member(): void
    {
        $groupCollection = GroupCollection::factory()->openToMembersOnly()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/group-collections/{$groupCollection->id}/membership")
            ->assertForbidden()
            ->assertJsonPath('message', 'You are not a member of this collection.');
    }

    public function test_leaving_a_private_collection_keeps_its_favorite_but_hides_it(): void
    {
        $member = User::factory()->create();
        $groupCollection = GroupCollection::factory()->private()->openToContributions()->create();
        $groupCollection->members()->attach($member);
        $member->favoriteGroupCollections()->attach($groupCollection);

        $this->actingAs($member)->deleteJson("/api/group-collections/{$groupCollection->id}/membership")->assertNoContent();

        $this->assertDatabaseHas('favorite_group_collections', ['user_id' => $member->id, 'group_collection_id' => $groupCollection->id]);
        $this->actingAs($member)->getJson('/api/favorites/group-collections')->assertJsonCount(0, 'data');
    }

    public function test_creator_changes_the_type_of_sharing_of_a_collection_member(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->openToMembersOnly()->createdBy($creator)->create();
        $groupCollection->members()->attach($member = User::factory()->create(), ['share_type' => 'full']);

        $this->actingAs($creator)
            ->patchJson("/api/group-collections/{$groupCollection->id}/members/{$member->id}", ['share_type' => 'partial'])
            ->assertOk()
            ->assertJsonPath('data.share_type', 'partial');

        $this->assertSame(ShareType::Partial, $groupCollection->fresh()->shareTypeOf($member));
    }
}
