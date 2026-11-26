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

    public function test_word_groups_lists_only_granted_groups(): void
    {
        $user = User::factory()->create();
        $grantedGroup = WordGroup::factory()->private()->openToContributions()->create();
        $grantedGroup->members()->attach($user);
        WordGroupAccessRequest::factory()->by($user)->create();
        WordGroupAccessRequest::factory()->by($user)->create([
            'via_group_collection_id' => GroupCollection::factory()->public()->create()->id,
        ]);
        WordGroupAccessRequest::factory()->by($user)->refused()->create();
        WordGroup::factory()->public()->create();

        $this->actingAs($user)
            ->getJson('/api/shared/word-groups')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$grantedGroup->id]);
    }

    public function test_group_collections_lists_only_granted_collections(): void
    {
        $user = User::factory()->create();
        $grantedCollection = GroupCollection::factory()->private()->openToContributions()->create();
        $grantedCollection->members()->attach($user);
        GroupCollectionAccessRequest::factory()->by($user)->create();
        GroupCollectionAccessRequest::factory()->by($user)->refused()->create();

        $this->actingAs($user)
            ->getJson('/api/shared/group-collections')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$grantedCollection->id]);
    }
}
