<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\Locale;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordGroupCollectionTargetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $wordGroup = WordGroup::factory()->public()->create();

        $this->getJson("/api/word-groups/{$wordGroup->id}/collection-targets")->assertUnauthorized();
    }

    public function test_returns_404_for_a_private_group_of_another_user(): void
    {
        $wordGroup = WordGroup::factory()->private()->create();

        $this->actingAs(User::factory()->create())
            ->getJson("/api/word-groups/{$wordGroup->id}/collection-targets")
            ->assertNotFound();
    }

    public function test_suggests_the_favorite_collections_the_user_can_add_the_group_to_most_recently_favorited_first(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->inLanguage(Locale::Fr)->create();
        $olderFavorite = GroupCollection::factory()->public()->openToContributions()->inLanguage(Locale::Fr)->create();
        $newerFavorite = GroupCollection::factory()->createdBy($user)->inLanguage(Locale::Fr)->create();
        $closedFavorite = GroupCollection::factory()->public()->closedToContributions()->inLanguage(Locale::Fr)->create();
        $englishFavorite = GroupCollection::factory()->createdBy($user)->inLanguage(Locale::En)->create();
        $this->travelTo(now()->subDay(), fn () => $user->favoriteGroupCollections()->attach($olderFavorite));
        $user->favoriteGroupCollections()->attach([$newerFavorite->id, $closedFavorite->id, $englishFavorite->id]);

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/collection-targets")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$newerFavorite->id, $olderFavorite->id])
            ->assertJsonPath('meta.suggestions', 'favorites');
    }

    public function test_suggests_the_own_collections_when_no_favorite_can_receive_the_group(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        $ownCollection = GroupCollection::factory()->createdBy($user)->create();
        $user->favoriteGroupCollections()->attach(GroupCollection::factory()->public()->closedToContributions()->create());
        GroupCollection::factory()->public()->openToContributions()->create();

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/collection-targets")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$ownCollection->id])
            ->assertJsonPath('meta.suggestions', 'own');
    }

    public function test_searches_the_collections_open_to_the_user_with_own_collections_first(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        $openCollection = GroupCollection::factory()->public()->openToContributions()->create(['name' => 'Animaux A']);
        $ownCollection = GroupCollection::factory()->createdBy($user)->create(['name' => 'Animaux B']);
        $membersOnlyCollection = GroupCollection::factory()->openToMembersOnly()->create(['name' => 'Animaux C']);
        $membersOnlyCollection->members()->attach($user);
        GroupCollection::factory()->openToMembersOnly()->create(['name' => 'Animaux interdits']);
        GroupCollection::factory()->public()->closedToContributions()->create(['name' => 'Animaux fermés']);
        GroupCollection::factory()->private()->openToContributions()->create(['name' => 'Animaux cachés']);
        GroupCollection::factory()->createdBy($user)->create(['name' => 'Fruits']);

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/collection-targets?q=anim")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$ownCollection->id, $openCollection->id, $membersOnlyCollection->id]);
    }

    public function test_finds_a_private_collection_shared_with_the_user_while_it_is_open(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        $sharedCollection = GroupCollection::factory()->private()->openToContributions()->create(['name' => 'Partagée']);
        $sharedCollection->members()->attach($user);
        $closedSharedCollection = GroupCollection::factory()->private()->closedToContributions()->create(['name' => 'Partagée fermée']);
        $closedSharedCollection->members()->attach($user);

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/collection-targets?q=partag")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$sharedCollection->id]);
    }

    public function test_flags_the_collections_that_already_contain_the_group(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->create();
        $containingCollection = GroupCollection::factory()->createdBy($user)
            ->hasAttached($wordGroup, ['added_by' => $user->id])
            ->create(['name' => 'Avec']);
        GroupCollection::factory()->createdBy($user)->create(['name' => 'Sans']);

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/collection-targets?q=a")
            ->assertOk()
            ->assertJsonPath('data.0.id', $containingCollection->id)
            ->assertJsonPath('data.*.contains_word_group', [true, false]);
    }

    public function test_returns_the_given_collection_only_while_the_group_can_be_added_to_it(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->createdBy($user)->inLanguage(Locale::Fr)->create();
        $target = GroupCollection::factory()->public()->openToContributions()->inLanguage(Locale::Fr)->create();
        $englishCollection = GroupCollection::factory()->createdBy($user)->inLanguage(Locale::En)->create();
        $closedCollection = GroupCollection::factory()->public()->closedToContributions()->inLanguage(Locale::Fr)->create();

        $this->actingAs($user)
            ->getJson("/api/word-groups/{$wordGroup->id}/collection-targets?group_collection_id={$target->id}")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$target->id]);
        foreach ([$englishCollection, $closedCollection] as $collection) {
            $this->actingAs($user)
                ->getJson("/api/word-groups/{$wordGroup->id}/collection-targets?group_collection_id={$collection->id}")
                ->assertOk()
                ->assertJsonCount(0, 'data');
        }
    }
}
