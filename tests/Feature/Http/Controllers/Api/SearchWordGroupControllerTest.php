<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchWordGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $this->getJson('/api/word-groups/search')->assertUnauthorized();
    }

    public function test_returns_matching_visible_groups_with_own_groups_first(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['name' => 'Bob']);
        $publicGroupOfOtherUser = WordGroup::factory()->public()->createdBy($otherUser)->create(['name' => 'Animaux marins']);
        $ownPrivateGroup = WordGroup::factory()->private()->createdBy($user)->create(['name' => 'Animaux']);
        WordGroup::factory()->private()->createdBy($otherUser)->create(['name' => 'Animaux secrets']);
        WordGroup::factory()->createdBy($user)->create(['name' => 'Fruits']);

        $response = $this->actingAs($user)->getJson('/api/word-groups/search?q=anim');

        $response->assertOk()
            ->assertJsonPath('data.*.id', [$ownPrivateGroup->id, $publicGroupOfOtherUser->id])
            ->assertJsonPath('data.1.creator', ['id' => $otherUser->id, 'name' => 'Bob']);
    }

    public function test_treats_like_wildcards_as_literal_characters(): void
    {
        $user = User::factory()->create();
        WordGroup::factory()->createdBy($user)->create(['name' => 'Fruits']);

        $this->actingAs($user)
            ->getJson('/api/word-groups/search?q=%25')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_returns_at_most_ten_groups(): void
    {
        $user = User::factory()->create();
        WordGroup::factory()->count(11)->createdBy($user)->create();

        $this->actingAs($user)->getJson('/api/word-groups/search')->assertOk()->assertJsonCount(10, 'data');
    }
}
