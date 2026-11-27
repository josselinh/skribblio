<?php

namespace Tests\Feature;

use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /* In memory: the file driver would also put the development server down. */
        config(['app.maintenance.driver' => 'array']);
        $this->app->maintenanceMode()->activate([]);
    }

    public function test_page_loads_get_the_maintenance_page_in_the_browser_language(): void
    {
        $this->withHeader('Accept-Language', 'en-GB,en;q=0.9')
            ->get('/dashboard')
            ->assertServiceUnavailable()
            ->assertSee('Skribblio Tools is under maintenance')
            ->assertSee('Try again');
    }

    public function test_maintenance_page_speaks_chinese_to_a_chinese_browser(): void
    {
        $this->withHeader('Accept-Language', 'zh-CN,zh;q=0.9')
            ->get('/explore')
            ->assertServiceUnavailable()
            ->assertSee('Skribblio Tools 正在维护');
    }

    public function test_maintenance_page_defaults_to_french(): void
    {
        $this->withHeader('Accept-Language', 'de-DE')
            ->get('/')
            ->assertServiceUnavailable()
            ->assertSee('Skribblio Tools est en maintenance');
    }

    public function test_api_requests_get_a_json_503(): void
    {
        $this->getJson('/api/search/word-groups')
            ->assertServiceUnavailable()
            ->assertJsonStructure(['message']);
    }
}
