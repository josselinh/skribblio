<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\GroupCollection;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_the_indexable_pages_only(): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        $publicGroup = WordGroup::factory()->public()->create();
        Word::factory()->for($publicGroup)->create();
        $emptyGroup = WordGroup::factory()->public()->create();
        $privateGroup = WordGroup::factory()->private()->create();
        Word::factory()->for($privateGroup)->create();
        $publicCollection = GroupCollection::factory()->public()->create();
        $publicCollection->wordGroups()->sync([$publicGroup->id]);
        $collectionOfPrivateGroups = GroupCollection::factory()->public()->create();
        $collectionOfPrivateGroups->wordGroups()->sync([$privateGroup->id]);
        $privateCollection = GroupCollection::factory()->private()->create();
        $privateCollection->wordGroups()->sync([$publicGroup->id]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('<loc>'.url('/').'</loc>', false)
            ->assertSee('<loc>'.url('/explore').'</loc>', false)
            ->assertSee('<loc>'.url("/groups/{$publicGroup->id}").'</loc><lastmod>2026-11-20T10:00:00+00:00</lastmod>', false)
            ->assertSee('<loc>'.url("/groups/{$publicGroup->id}").'?hl=fr</loc>', false)
            ->assertSee('<xhtml:link rel="alternate" hreflang="fr" href="'.url("/groups/{$publicGroup->id}").'?hl=fr"/>', false)
            ->assertSee('<loc>'.url("/collections/{$publicCollection->id}").'</loc>', false)
            ->assertDontSee($emptyGroup->id)
            ->assertDontSee($privateGroup->id)
            ->assertDontSee($collectionOfPrivateGroups->id)
            ->assertDontSee($privateCollection->id);
    }
}
