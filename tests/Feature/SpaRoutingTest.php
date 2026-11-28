<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SpaRoutingTest extends TestCase
{
    #[TestWith(['/'])]
    #[TestWith(['/dashboard'])]
    #[TestWith(['/login'])]
    #[TestWith(['/reset-password/some-token'])]
    #[TestWith(['/email/verify'])]
    public function test_renders_spa_shell_for_front_end_paths(string $path): void
    {
        $this->get($path)->assertOk()->assertViewIs('app');
    }

    public function test_unknown_front_end_path_renders_spa_shell_with_404(): void
    {
        $this->get('/any/unknown/page')->assertNotFound()->assertViewIs('app');
    }

    public function test_unknown_api_path_returns_404_json(): void
    {
        $this->getJson('/api/does-not-exist')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }
}
