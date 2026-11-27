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
                'theme' => 'system',
                'style' => 'sober',
                'locale' => 'fr',
                'content_locale' => 'fr',
                'email_notifications' => [
                    'word_group_access_requested' => false,
                    'group_collection_access_requested' => false,
                    'access_request_decided' => false,
                    'share_type_changed' => false,
                    'word_group_favorited' => false,
                    'group_collection_favorited' => false,
                ],
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ]);
    }
}
