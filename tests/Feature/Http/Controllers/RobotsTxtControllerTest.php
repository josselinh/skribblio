<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

class RobotsTxtControllerTest extends TestCase
{
    public function test_points_to_the_sitemap_and_leaves_the_api_to_crawlers(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk()
            ->assertSee('Disallow: /dashboard')
            ->assertSee('Sitemap: '.url('/sitemap.xml'))
            ->assertDontSee('/api');
    }
}
