<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\ShareType;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
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
            ->assertJsonPath('message', 'Vous n’êtes pas membre de cette collection.');
    }

    public function test_leaving_a_private_collection_removes_it_from_favorites_but_not_its_groups(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $publicGroup = WordGroup::factory()->public()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->private()->openToContributions()->createdBy($creator)->hasAttached($publicGroup)->create();
        $groupCollection->members()->attach($member);
        $member->favoriteGroupCollections()->attach($groupCollection);
        $member->favoriteWordGroups()->attach($publicGroup);

        $this->actingAs($member)->deleteJson("/api/group-collections/{$groupCollection->id}/membership")->assertNoContent();

        $this->assertDatabaseMissing('favorite_group_collections', ['user_id' => $member->id, 'group_collection_id' => $groupCollection->id]);
        $this->assertDatabaseHas('favorite_word_groups', ['user_id' => $member->id, 'word_group_id' => $publicGroup->id]);
    }

    public function test_leaving_a_public_collection_keeps_it_in_favorites(): void
    {
        $member = User::factory()->create();
        $groupCollection = GroupCollection::factory()->openToMembersOnly()->create();
        $groupCollection->members()->attach($member);
        $member->favoriteGroupCollections()->attach($groupCollection);

        $this->actingAs($member)->deleteJson("/api/group-collections/{$groupCollection->id}/membership")->assertNoContent();

        $this->assertDatabaseHas('favorite_group_collections', ['user_id' => $member->id, 'group_collection_id' => $groupCollection->id]);
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
