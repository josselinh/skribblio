<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_accessible_without_logged_in_user(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee($this->href('sentence.export'), false);
        $response->assertDontSee($this->href('sentence.index'), false);
        $response->assertDontSee($this->href('group.index'), false);
    }

    public function test_home_page_is_accessible_with_logged_in_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee($this->href('sentence.export'), false);
        $response->assertSee($this->href('sentence.index'), false);
        $response->assertSee($this->href('group.index'), false);
    }

    /**
     * Build the exact href attribute of a named route, so that
     * "/sentence" does not match "/sentence/export".
     */
    private function href(string $routeName): string
    {
        return 'href="'.route($routeName).'"';
    }
}
