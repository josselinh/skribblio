<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_link_verifies_email_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get($this->verificationUrlFor($user));

        $response->assertRedirect('/dashboard?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_link_redirects_guest_to_spa_login(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->get($this->verificationUrlFor($user));

        $response->assertRedirect('/login');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    private function verificationUrlFor(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);
    }
}
