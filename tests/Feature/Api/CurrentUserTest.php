<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_returns_current_user_including_unverified_ones(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertOk()->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => null,
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ]);
    }
}
