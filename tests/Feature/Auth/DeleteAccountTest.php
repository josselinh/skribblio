<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_the_account_and_logs_out(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp,
        ]);
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'token']);

        $this->actingAs($user)->deleteJson('/api/user', ['password' => 'password'])->assertNoContent();

        $this->assertModelMissing($user);
        $this->assertGuest('web');
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_unverified_user_can_delete_their_account(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->deleteJson('/api/user', ['password' => 'password'])->assertNoContent();

        $this->assertModelMissing($user);
    }

    public function test_rejects_a_wrong_password_with_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/user', ['password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'Le mot de passe est incorrect.']);

        $this->assertModelExists($user);
    }

    public function test_returns_401_for_guest(): void
    {
        $this->deleteJson('/api/user', ['password' => 'password'])->assertUnauthorized();
    }

    public function test_words_added_to_other_groups_are_listed_without_author(): void
    {
        $author = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();
        Word::factory()->for($wordGroup)->createdBy($author)->create(['name' => 'girafe']);

        $this->actingAs($author)->deleteJson('/api/user', ['password' => 'password'])->assertNoContent();

        $this->actingAs($wordGroup->creator)
            ->getJson("/api/word-groups/{$wordGroup->id}/words")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'girafe')
            ->assertJsonPath('data.0.creator', null)
            ->assertJsonPath('data.0.can.update', true);
    }
}
