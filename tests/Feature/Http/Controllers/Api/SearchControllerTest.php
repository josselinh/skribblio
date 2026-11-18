<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['/api/search/word-groups'])]
    #[TestWith(['/api/search/group-collections'])]
    public function test_returns_401_for_guest(string $uri): void
    {
        $this->getJson($uri)->assertUnauthorized();
    }

    public function test_word_groups_returns_own_and_public_groups_matching_the_name_but_not_private_groups_of_others(): void
    {
        $user = User::factory()->create();
        $ownPrivateGroup = WordGroup::factory()->private()->createdBy($user)->create(['name' => 'Mes chats']);
        $publicGroup = WordGroup::factory()->public()->create(['name' => 'Chats célèbres']);
        WordGroup::factory()->private()->create(['name' => 'Chats secrets']);
        WordGroup::factory()->public()->create(['name' => 'Chiens']);

        $response = $this->actingAs($user)->getJson('/api/search/word-groups?q=chat');

        $response->assertOk()->assertJsonPath('meta.total', 2);
        $this->assertEqualsCanonicalizing([$ownPrivateGroup->id, $publicGroup->id], $response->json('data.*.id'));
    }

    public function test_word_groups_lists_names_starting_with_the_search_first_then_most_recently_updated(): void
    {
        $user = User::factory()->create();
        $containsRecent = WordGroup::factory()->public()->create(['name' => 'Le chat botté', 'updated_at' => now()]);
        $startsOld = WordGroup::factory()->public()->create(['name' => 'Chats', 'updated_at' => now()->subWeek()]);
        $startsRecent = WordGroup::factory()->public()->create(['name' => 'Chaton', 'updated_at' => now()->subDay()]);

        $this->actingAs($user)
            ->getJson('/api/search/word-groups?q=chat')
            ->assertJsonPath('data.*.id', [$startsRecent->id, $startsOld->id, $containsRecent->id]);
    }

    public function test_word_groups_include_creator_and_words_count(): void
    {
        $creator = User::factory()->create(['name' => 'Bob']);
        $wordGroup = WordGroup::factory()->public()->createdBy($creator)->create();
        Word::factory()->count(3)->for($wordGroup)->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/search/word-groups')
            ->assertJsonPath('data.0.creator', ['id' => $creator->id, 'name' => 'Bob'])
            ->assertJsonPath('data.0.words_count', 3);
    }

    public function test_word_groups_filter_by_author(): void
    {
        $user = User::factory()->create();
        $ownGroup = WordGroup::factory()->createdBy($user)->create();
        $otherUsersGroup = WordGroup::factory()->public()->create();

        $this->actingAs($user)->getJson('/api/search/word-groups?author=me')->assertJsonPath('data.*.id', [$ownGroup->id]);
        $this->actingAs($user)->getJson('/api/search/word-groups?author=others')->assertJsonPath('data.*.id', [$otherUsersGroup->id]);
    }

    public function test_treats_like_wildcards_as_literal_characters(): void
    {
        $user = User::factory()->create();
        WordGroup::factory()->createdBy($user)->create(['name' => 'Fruits']);

        $this->actingAs($user)->getJson('/api/search/word-groups?q=_')->assertJsonCount(0, 'data');
    }

    public function test_paginates_with_per_page_and_keeps_filters_in_page_links(): void
    {
        $user = User::factory()->create();
        WordGroup::factory()->count(7)->createdBy($user)->create();

        $response = $this->actingAs($user)->getJson('/api/search/word-groups?author=me&per_page=6');

        $response->assertJsonCount(6, 'data')->assertJsonPath('meta.last_page', 2);
        $this->assertStringContainsString('author=me', $response->json('links.next'));
    }

    #[TestWith(['author=friends', 'author'])]
    #[TestWith(['per_page=100', 'per_page'])]
    public function test_rejects_invalid_filters_with_422(string $query, string $field): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson("/api/search/word-groups?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    }

    public function test_group_collections_returns_matching_visible_collections_with_creator_and_groups_count(): void
    {
        $user = User::factory()->create();
        $creator = User::factory()->create(['name' => 'Bob']);
        $publicCollection = GroupCollection::factory()->public()->createdBy($creator)
            ->hasAttached(WordGroup::factory()->count(2)->public()->createdBy($creator)->create())
            ->create(['name' => 'Soirée chats']);
        GroupCollection::factory()->private()->create(['name' => 'Chats privés']);
        GroupCollection::factory()->public()->create(['name' => 'Soirée chiens']);

        $this->actingAs($user)
            ->getJson('/api/search/group-collections?q=chat')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$publicCollection->id])
            ->assertJsonPath('data.0.creator.name', 'Bob')
            ->assertJsonPath('data.0.word_groups_count', 2);
    }

    public function test_group_collections_filter_by_author(): void
    {
        $user = User::factory()->create();
        $ownCollection = GroupCollection::factory()->createdBy($user)->create();
        GroupCollection::factory()->public()->create();

        $this->actingAs($user)
            ->getJson('/api/search/group-collections?author=me')
            ->assertJsonPath('data.*.id', [$ownCollection->id]);
    }
}
