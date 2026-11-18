<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupCollectionWordGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_adds_public_group_of_another_user_and_touches_the_collection(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)->create(['updated_at' => now()->subWeek()]);
        $publicGroup = WordGroup::factory()->public()->create();

        $this->actingAs($creator)
            ->postJson("/api/group-collections/{$groupCollection->id}/word-groups", ['word_group_id' => $publicGroup->id])
            ->assertNoContent();

        $this->assertTrue($groupCollection->wordGroups()->whereKey($publicGroup->id)->exists());
        $this->assertTrue($groupCollection->fresh()->updated_at->isToday());
    }

    public function test_store_ignores_group_already_in_the_collection(): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)->hasAttached($wordGroup)->create();

        $this->actingAs($creator)
            ->postJson("/api/group-collections/{$groupCollection->id}/word-groups", ['word_group_id' => $wordGroup->id])
            ->assertNoContent();

        $this->assertSame(1, $groupCollection->wordGroups()->count());
    }

    public function test_store_rejects_private_group_of_another_user_as_not_found_with_422(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)->create();
        $privateGroup = WordGroup::factory()->private()->create();

        $this->actingAs($creator)
            ->postJson("/api/group-collections/{$groupCollection->id}/word-groups", ['word_group_id' => $privateGroup->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word_group_id' => 'Ce groupe est introuvable.']);

        $this->assertFalse($groupCollection->wordGroups()->whereKey($privateGroup->id)->exists());
    }

    public function test_store_returns_403_for_another_user_on_public_collection(): void
    {
        $otherUser = User::factory()->create();
        $groupCollection = GroupCollection::factory()->public()->create();
        $ownGroup = WordGroup::factory()->createdBy($otherUser)->create();

        $this->actingAs($otherUser)
            ->postJson("/api/group-collections/{$groupCollection->id}/word-groups", ['word_group_id' => $ownGroup->id])
            ->assertForbidden();
    }

    public function test_destroy_removes_group_and_touches_the_collection(): void
    {
        $creator = User::factory()->create();
        $removedGroup = WordGroup::factory()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)
            ->hasAttached([$removedGroup, WordGroup::factory()->createdBy($creator)->create()])
            ->create(['updated_at' => now()->subWeek()]);

        $this->actingAs($creator)
            ->deleteJson("/api/group-collections/{$groupCollection->id}/word-groups/{$removedGroup->id}")
            ->assertNoContent();

        $this->assertFalse($groupCollection->wordGroups()->whereKey($removedGroup->id)->exists());
        $this->assertModelExists($removedGroup);
        $this->assertTrue($groupCollection->fresh()->updated_at->isToday());
    }

    public function test_destroy_refuses_to_remove_the_last_group_with_422(): void
    {
        $creator = User::factory()->create();
        $lastGroup = WordGroup::factory()->createdBy($creator)->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)->hasAttached($lastGroup)->create();

        $this->actingAs($creator)
            ->deleteJson("/api/group-collections/{$groupCollection->id}/word-groups/{$lastGroup->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word_group_id' => 'Une collection doit contenir au moins un groupe.']);

        $this->assertSame(1, $groupCollection->wordGroups()->count());
    }

    public function test_destroy_returns_404_for_group_not_in_the_collection(): void
    {
        $creator = User::factory()->create();
        $groupCollection = GroupCollection::factory()->createdBy($creator)->create();
        $otherGroup = WordGroup::factory()->createdBy($creator)->create();

        $this->actingAs($creator)
            ->deleteJson("/api/group-collections/{$groupCollection->id}/word-groups/{$otherGroup->id}")
            ->assertNotFound();
    }

    public function test_destroy_returns_403_for_another_user_on_public_collection(): void
    {
        $wordGroup = WordGroup::factory()->public()->create();
        $groupCollection = GroupCollection::factory()->public()
            ->hasAttached([$wordGroup, WordGroup::factory()->public()->create()])
            ->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/group-collections/{$groupCollection->id}/word-groups/{$wordGroup->id}")
            ->assertForbidden();

        $this->assertSame(2, $groupCollection->wordGroups()->count());
    }
}
