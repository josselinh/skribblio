<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_unverified_user_with_uuid_and_sends_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ]);

        $response->assertCreated();
        $this->assertAuthenticated();

        $user = User::firstWhere('email', 'alice@example.com');
        $this->assertNotNull($user);
        $this->assertTrue(Str::isUuid($user->id));
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_with_empty_payload_returns_422_with_required_errors(): void
    {
        $response = $this->postJson('/register', []);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'name' => 'Le champ pseudo est obligatoire.',
            'email',
            'password',
        ]);
        $this->assertGuest();
    }
}
