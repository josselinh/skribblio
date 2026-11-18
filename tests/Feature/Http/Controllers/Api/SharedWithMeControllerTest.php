<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\GroupCollection;
use App\Models\GroupCollectionAccessRequest;
use App\Models\User;
use App\Models\WordGroup;
use App\Models\WordGroupAccessRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedWithMeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_groups_lists_granted_groups_and_pending_link_requests_but_not_requests_sent_from_a_collection(): void
    {
        $user = User::factory()->create();
        $grantedGroup = WordGroup::factory()->private()->create(['updated_at' => now()]);
        $grantedGroup->members()->attach($user);
        $pendingRequest = WordGroupAccessRequest::factory()
            ->for(WordGroup::factory()->private()->state(['updated_at' => now()->subDay()]), 'item')
            ->by($user)
            ->create();
        WordGroupAccessRequest::factory()->by($user)->create([
            'via_group_collection_id' => GroupCollection::factory()->public()->create()->id,
        ]);
        WordGroupAccessRequest::factory()->by($user)->refused()->create();
        WordGroup::factory()->public()->create();

        $this->actingAs($user)
            ->getJson('/api/shared/word-groups')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$grantedGroup->id, $pendingRequest->word_group_id])
            ->assertJsonPath('data.*.access_status', ['granted', 'pending'])
            ->assertJsonPath('data.*.is_accessible', [true, false]);
    }

    public function test_group_collections_lists_granted_and_pending_collections(): void
    {
        $user = User::factory()->create();
        $grantedCollection = GroupCollection::factory()->private()->create(['updated_at' => now()]);
        $grantedCollection->members()->attach($user);
        GroupCollectionAccessRequest::factory()
            ->for(GroupCollection::factory()->private()->state(['updated_at' => now()->subDay()]), 'item')
            ->by($user)
            ->create();

        $this->actingAs($user)
            ->getJson('/api/shared/group-collections')
            ->assertOk()
            ->assertJsonPath('data.*.access_status', ['granted', 'pending']);
    }
}
