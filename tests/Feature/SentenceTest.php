<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Sentence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SentenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sentence_index_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->for($user)->create();
        Sentence::factory()->for($user)->for($group)->create();

        $response = $this->actingAs($user)->get(route('sentence.index'));

        $response->assertStatus(200);
    }

    public function test_sentence_add_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('sentence.add'));

        $response->assertStatus(200);
    }

    public function test_sentence_export_can_be_rendered(): void
    {
        $response = $this->get(route('sentence.export'));

        $response->assertStatus(200);
    }

    public function test_group_index_can_be_rendered(): void
    {
        $user = User::factory()->create();
        Group::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('group.index'));

        $response->assertStatus(200);
    }

    public function test_group_add_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('group.add'));

        $response->assertStatus(200);
    }
}
