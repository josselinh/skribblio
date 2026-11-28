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

    public function test_word_groups_returns_only_public_groups_to_guest_without_creator_or_favorite(): void
    {
        $publicGroup = WordGroup::factory()->public()->create(['name' => 'Chats célèbres']);
        WordGroup::factory()->private()->create(['name' => 'Chats secrets']);

        $response = $this->getJson('/api/search/word-groups?q=chat');

        $response->assertOk()
            ->assertJsonPath('data.*.id', [$publicGroup->id])
            ->assertJsonMissingPath('data.0.creator')
            ->assertJsonMissingPath('data.0.is_favorite')
            ->assertJsonPath('data.0.can.update', false);
    }

    public function test_group_collections_returns_only_public_collections_to_guest(): void
    {
        $publicCollection = GroupCollection::factory()->public()->create();
        GroupCollection::factory()->private()->create();

        $this->getJson('/api/search/group-collections')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$publicCollection->id])
            ->assertJsonMissingPath('data.0.creator');
    }

    public function test_treats_unverified_user_as_guest(): void
    {
        $user = User::factory()->unverified()->create();
        WordGroup::factory()->private()->createdBy($user)->create();

        $this->actingAs($user)->getJson('/api/search/word-groups')->assertOk()->assertJsonCount(0, 'data');
    }

    #[TestWith(['author=me', 'author', 'Log in to filter by author.'])]
    #[TestWith(['author=others', 'author', 'Log in to filter by author.'])]
    #[TestWith(['sort=popular', 'sort', 'Log in to use this sort.'])]
    #[TestWith(['sort=largest', 'sort', 'Log in to use this sort.'])]
    public function test_rejects_members_only_filters_from_guest_with_422(string $query, string $field, string $message): void
    {
        $this->getJson("/api/search/word-groups?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);
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

    public function test_word_groups_sort_by_popularity_then_most_recently_updated(): void
    {
        $user = User::factory()->create();
        $unfavoritedRecent = WordGroup::factory()->public()->create(['updated_at' => now()]);
        $favoritedOnce = WordGroup::factory()->public()->create(['updated_at' => now()->subWeek()]);
        $favoritedTwice = WordGroup::factory()->public()->create(['updated_at' => now()->subMonth()]);
        $favoritedOnce->favoritedBy()->attach(User::factory()->create());
        $favoritedTwice->favoritedBy()->attach(User::factory()->count(2)->create());

        $this->actingAs($user)
            ->getJson('/api/search/word-groups?sort=popular')
            ->assertJsonPath('data.*.id', [$favoritedTwice->id, $favoritedOnce->id, $unfavoritedRecent->id]);
    }

    public function test_word_groups_sort_by_words_count(): void
    {
        $user = User::factory()->create();
        $smallGroup = WordGroup::factory()->public()->create(['updated_at' => now()]);
        $largeGroup = WordGroup::factory()->public()->create(['updated_at' => now()->subWeek()]);
        Word::factory()->count(3)->for($largeGroup)->create();
        Word::factory()->for($smallGroup)->create();

        $this->actingAs($user)
            ->getJson('/api/search/word-groups?sort=largest')
            ->assertJsonPath('data.*.id', [$largeGroup->id, $smallGroup->id]);
    }

    public function test_word_groups_sort_by_name_after_the_name_relevance(): void
    {
        $user = User::factory()->create();
        $containsA = WordGroup::factory()->public()->create(['name' => 'Animaux du chat']);
        $startsZ = WordGroup::factory()->public()->create(['name' => 'Chats zen']);
        $startsA = WordGroup::factory()->public()->create(['name' => 'Chats agiles']);

        $this->actingAs($user)
            ->getJson('/api/search/word-groups?q=chat&sort=name')
            ->assertJsonPath('data.*.id', [$startsA->id, $startsZ->id, $containsA->id]);
    }

    public function test_word_groups_sort_in_the_direction_given(): void
    {
        $user = User::factory()->create();
        $oldGroup = WordGroup::factory()->public()->create(['name' => 'Animaux', 'updated_at' => now()->subWeek()]);
        $recentGroup = WordGroup::factory()->public()->create(['name' => 'Zoo', 'updated_at' => now()]);

        $this->actingAs($user)
            ->getJson('/api/search/word-groups?direction=asc')
            ->assertJsonPath('data.*.id', [$oldGroup->id, $recentGroup->id]);
        $this->actingAs($user)
            ->getJson('/api/search/word-groups?sort=name&direction=desc')
            ->assertJsonPath('data.*.id', [$recentGroup->id, $oldGroup->id]);
    }

    public function test_group_collections_sort_by_popularity_and_by_groups_count(): void
    {
        $user = User::factory()->create();
        $popularCollection = GroupCollection::factory()->public()->create(['updated_at' => now()->subWeek()]);
        $largeCollection = GroupCollection::factory()->public()
            ->hasAttached(WordGroup::factory()->count(3)->public()->create())
            ->create(['updated_at' => now()->subMonth()]);
        $popularCollection->favoritedBy()->attach(User::factory()->create());

        $this->actingAs($user)
            ->getJson('/api/search/group-collections?sort=popular')
            ->assertJsonPath('data.*.id', [$popularCollection->id, $largeCollection->id]);
        $this->actingAs($user)
            ->getJson('/api/search/group-collections?sort=largest')
            ->assertJsonPath('data.*.id', [$largeCollection->id, $popularCollection->id]);
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
    #[TestWith(['sort=random', 'sort'])]
    #[TestWith(['direction=up', 'direction'])]
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
