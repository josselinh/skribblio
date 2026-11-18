<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_renaming_keeps_the_email_verified(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'alice@example.com']);

        $this->actingAs($user)
            ->putJson('/user/profile-information', ['name' => 'Alice Martin', 'email' => 'alice@example.com'])
            ->assertOk();

        $this->assertSame('Alice Martin', $user->fresh()->name);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Notification::assertNothingSent();
    }

    public function test_changing_email_requires_verifying_the_new_address(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'alice@example.com']);

        $this->actingAs($user)
            ->putJson('/user/profile-information', ['name' => $user->name, 'email' => 'alice@example.org'])
            ->assertOk();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->actingAs($user->fresh())->getJson('/api/word-groups')->assertForbidden();
    }

    public function test_rejects_email_of_another_user_with_422(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/user/profile-information', ['name' => $user->name, 'email' => 'taken@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'La valeur du champ adresse e-mail est déjà utilisée.']);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/user/password', [
                'current_password' => 'wrong-password',
                'password' => 'a-new-strong-password',
                'password_confirmation' => 'a-new-strong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);

        $this->actingAs($user)
            ->putJson('/user/password', [
                'current_password' => 'password',
                'password' => 'a-new-strong-password',
                'password_confirmation' => 'a-new-strong-password',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('a-new-strong-password', $user->fresh()->password));
    }
}
