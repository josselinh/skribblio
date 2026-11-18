<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_email_points_to_spa_reset_page(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'alice@example.com']);

        $this->postJson('/forgot-password', ['email' => 'alice@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            return $url === url("/reset-password/{$notification->token}?email=alice%40example.com");
        });
    }
}
