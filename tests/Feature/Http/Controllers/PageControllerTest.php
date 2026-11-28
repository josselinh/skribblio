<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\Locale;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_group_is_indexable_and_described_by_its_best_scored_words_in_its_language(): void
    {
        $wordGroup = WordGroup::factory()->public()->inLanguage(Locale::En)->create(['name' => 'Farm animals']);
        $voter = User::factory()->create();
        Word::factory()->for($wordGroup)->create(['name' => 'zebu'])->voters()->attach($voter, ['value' => 1]);
        foreach (['cow', 'duck', 'goat', 'hen', 'horse', 'lamb', 'pig', 'sheep'] as $name) {
            Word::factory()->for($wordGroup)->create(['name' => $name]);
        }

        $response = $this->get("/groups/{$wordGroup->id}?sort=name&page=2");

        $response->assertOk()
            ->assertSee('<title>Farm animals · Skribblio Tools</title>', false)
            ->assertSee('<meta name="description" content="A list of 9 words to export to Skribbl.io: zebu, cow, duck, goat, hen, horse, lamb, pig…">', false)
            ->assertSee('<link rel="canonical" href="'.url("/groups/{$wordGroup->id}").'">', false)
            ->assertDontSee('noindex');
    }

    public function test_public_group_without_words_is_not_indexed(): void
    {
        $wordGroup = WordGroup::factory()->public()->create();

        $this->get("/groups/{$wordGroup->id}")->assertOk()->assertSee('<meta name="robots" content="noindex">', false);
    }

    public function test_private_group_is_not_found_for_guests_and_other_users(): void
    {
        $wordGroup = WordGroup::factory()->private()->create(['name' => 'Secret list']);
        Word::factory()->for($wordGroup)->create();

        $this->get("/groups/{$wordGroup->id}")->assertNotFound()->assertViewIs('app')->assertDontSee('Secret list');
        $this->actingAs(User::factory()->create())->get("/groups/{$wordGroup->id}")->assertNotFound()->assertDontSee('Secret list');
    }

    public function test_private_group_is_shown_to_its_creator_without_being_indexed(): void
    {
        $creator = User::factory()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create(['name' => 'Secret list']);
        Word::factory()->for($wordGroup)->create();

        $response = $this->actingAs($creator)->get("/groups/{$wordGroup->id}");

        $response->assertOk()
            ->assertSee('<title>Secret list · Skribblio Tools</title>', false)
            ->assertSee('<meta name="robots" content="noindex">', false);
    }

    public function test_private_group_is_not_found_for_its_creator_whose_email_is_not_verified(): void
    {
        $creator = User::factory()->unverified()->create();
        $wordGroup = WordGroup::factory()->private()->createdBy($creator)->create();

        $this->actingAs($creator)->get("/groups/{$wordGroup->id}")->assertNotFound();
    }

    public function test_unknown_or_malformed_group_id_is_not_found(): void
    {
        $this->get('/groups/0b9f6a5e-2a1d-4c8e-9a43-6a0d7f1c2b3e')->assertNotFound()->assertViewIs('app');
        $this->get('/groups/not-a-uuid')->assertNotFound()->assertViewIs('app');
    }

    public function test_public_collection_is_described_by_its_public_groups_only(): void
    {
        $groupCollection = GroupCollection::factory()->public()->inLanguage(Locale::Fr)->create(['name' => 'Noël']);
        $groupCollection->wordGroups()->detach();
        $groupCollection->wordGroups()->attach([
            WordGroup::factory()->public()->inLanguage(Locale::Fr)->create(['name' => 'Cadeaux'])->id,
            WordGroup::factory()->public()->inLanguage(Locale::Fr)->create(['name' => 'Animaux'])->id,
            WordGroup::factory()->private()->inLanguage(Locale::Fr)->create(['name' => 'Caché'])->id,
        ]);

        $response = $this->get("/collections/{$groupCollection->id}");

        $response->assertOk()
            ->assertSee('<title>Noël · Skribblio Tools</title>', false)
            ->assertSee('<meta name="description" content="Collection de 2 groupes de mots pour Skribbl.io : Animaux, Cadeaux">', false)
            ->assertDontSee('Caché')
            ->assertDontSee('noindex');
    }

    public function test_public_collection_showing_no_group_is_not_indexed(): void
    {
        $groupCollection = GroupCollection::factory()->public()->create();
        $groupCollection->wordGroups()->detach();
        $groupCollection->wordGroups()->attach(WordGroup::factory()->private()->create());

        $this->get("/collections/{$groupCollection->id}")->assertOk()->assertSee('<meta name="robots" content="noindex">', false);
    }

    public function test_private_collection_is_not_found_for_guests(): void
    {
        $groupCollection = GroupCollection::factory()->private()->create();

        $this->get("/collections/{$groupCollection->id}")->assertNotFound();
    }

    public function test_explore_is_indexed_without_its_filters_but_not_a_search(): void
    {
        $this->get('/explore?type=collections&page=3')->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/explore').'">', false)
            ->assertDontSee('noindex');
        $this->get('/explore?q=noel')->assertOk()->assertSee('<meta name="robots" content="noindex">', false);
    }

    public function test_explore_is_in_english_by_default_and_names_its_french_version(): void
    {
        $response = $this->get('/explore');

        $response->assertOk()
            ->assertSee('<title>Explore · Skribblio Tools</title>', false)
            ->assertSee('<link rel="canonical" href="'.url('/explore').'">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/explore').'">', false)
            ->assertSee('<link rel="alternate" hreflang="fr" href="'.url('/explore').'?hl=fr">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/explore').'">', false);
    }

    public function test_explore_asked_in_french_is_in_french_with_its_own_canonical_address(): void
    {
        $response = $this->get('/explore?hl=fr&type=collections');

        $response->assertOk()
            ->assertSee('<html lang="fr">', false)
            ->assertSee('<title>Explorer · Skribblio Tools</title>', false)
            ->assertSee('<link rel="canonical" href="'.url('/explore').'?hl=fr">', false);
    }

    public function test_a_language_that_is_not_indexed_keeps_the_default_canonical_address(): void
    {
        $this->get('/explore?hl=zh')->assertOk()
            ->assertSee('<title>探索 · Skribblio Tools</title>', false)
            ->assertSee('<link rel="canonical" href="'.url('/explore').'">', false);
    }

    public function test_german_group_is_described_in_german(): void
    {
        $wordGroup = WordGroup::factory()->public()->inLanguage(Locale::De)->create(['name' => 'Tiere']);
        Word::factory()->for($wordGroup)->create(['name' => 'Hund']);
        Word::factory()->for($wordGroup)->create(['name' => 'Katze']);

        $this->get("/groups/{$wordGroup->id}?hl=fr")->assertOk()
            ->assertSee('<meta name="description" content="Eine Liste mit 2 Wörtern für Skribbl.io: Hund, Katze">', false)
            ->assertSee('<link rel="canonical" href="'.url("/groups/{$wordGroup->id}").'?hl=fr">', false);
    }

    public function test_pages_that_are_not_indexed_name_no_language_version(): void
    {
        $this->get('/explore?q=noel')->assertOk()->assertDontSee('hreflang');
    }

    public function test_home_is_indexable(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<title>Skribblio Tools</title>', false)
            ->assertSee('<link rel="canonical" href="'.url('/').'/">', false)
            ->assertSee('<link rel="alternate" hreflang="fr" href="'.url('/').'/?hl=fr">', false)
            ->assertDontSee('noindex');
    }

    public function test_application_pages_are_not_indexed(): void
    {
        $this->get('/groups/new')->assertOk()->assertSee('<meta name="robots" content="noindex">', false);
    }
}
