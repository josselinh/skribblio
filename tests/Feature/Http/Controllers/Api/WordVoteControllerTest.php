<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\WordVote;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordVoteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $word = Word::factory()->for(WordGroup::factory()->public()->openToContributions())->create();

        $this->putJson("/api/words/{$word->id}/vote", ['vote' => 'up'])->assertUnauthorized();
    }

    public function test_update_records_the_vote_and_returns_the_word_with_its_counts(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->public()->openToContributions())->create();
        $word->voteFor(User::factory()->create(), WordVote::Up);

        $response = $this->actingAs($user)->putJson("/api/words/{$word->id}/vote", ['vote' => 'down']);

        $response->assertOk()
            ->assertJsonPath('data.upvotes_count', 1)
            ->assertJsonPath('data.downvotes_count', 1)
            ->assertJsonPath('data.my_vote', 'down');
        $this->assertDatabaseHas('word_votes', ['word_id' => $word->id, 'user_id' => $user->id, 'value' => -1]);
    }

    public function test_update_replaces_the_previous_vote_of_the_user(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->public()->openToContributions())->create();
        $word->voteFor($user, WordVote::Down);

        $this->actingAs($user)
            ->putJson("/api/words/{$word->id}/vote", ['vote' => 'up'])
            ->assertOk()
            ->assertJsonPath('data.upvotes_count', 1)
            ->assertJsonPath('data.downvotes_count', 0)
            ->assertJsonPath('data.my_vote', 'up');
        $this->assertDatabaseCount('word_votes', 1);
    }

    public function test_update_rejects_an_unknown_vote_with_422(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->public()->openToContributions())->create();

        $this->actingAs($user)
            ->putJson("/api/words/{$word->id}/vote", ['vote' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['vote' => 'Le champ vote sélectionné est invalide.']);
        $this->assertDatabaseCount('word_votes', 0);
    }

    public function test_author_votes_for_their_own_word(): void
    {
        $author = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->public()->openToContributions())->createdBy($author)->create();

        $this->actingAs($author)->putJson("/api/words/{$word->id}/vote", ['vote' => 'up'])->assertOk();

        $this->assertDatabaseHas('word_votes', ['word_id' => $word->id, 'user_id' => $author->id, 'value' => 1]);
    }

    public function test_update_returns_404_for_a_word_of_a_private_group_of_another_user(): void
    {
        $word = Word::factory()->for(WordGroup::factory()->private()->openToContributions())->create();

        $this->actingAs(User::factory()->create())
            ->putJson("/api/words/{$word->id}/vote", ['vote' => 'up'])
            ->assertNotFound();
    }

    public function test_destroy_removes_the_vote_of_the_user_only(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->for(WordGroup::factory()->public()->openToContributions())->create();
        $word->voteFor($user, WordVote::Up);
        $word->voteFor(User::factory()->create(), WordVote::Up);

        $this->actingAs($user)
            ->deleteJson("/api/words/{$word->id}/vote")
            ->assertOk()
            ->assertJsonPath('data.upvotes_count', 1)
            ->assertJsonPath('data.my_vote', null);
        $this->assertDatabaseMissing('word_votes', ['word_id' => $word->id, 'user_id' => $user->id]);
    }

    public function test_votes_stay_open_once_the_group_is_closed_to_contributions(): void
    {
        $user = User::factory()->create();
        $wordGroup = WordGroup::factory()->public()->openToContributions()->create();
        $word = Word::factory()->for($wordGroup)->create();
        $word->voteFor($user, WordVote::Up);
        $wordGroup->update(['accepts_contributions' => false]);

        $this->actingAs($user)->deleteJson("/api/words/{$word->id}/vote")->assertOk();

        $this->assertDatabaseMissing('word_votes', ['word_id' => $word->id, 'user_id' => $user->id]);
    }
}
