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
        $response->assertDontSee(route('sentence.add', ['group' => $group->id]));
    }

    public function test_sentence_index_shows_add_button_when_filtered_by_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('sentence.index', ['group' => $group->id]));

        $response->assertStatus(200);
        $response->assertSee(route('sentence.add', ['group' => $group->id]));
    }

    public function test_sentence_index_displays_lock_icon_for_public_unalterable_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->for($user)->create([
            'visibility' => Group::VISIBILITY_PUBLIC_UNALTERABLE,
        ]);
        Sentence::factory()->for($user)->for($group)->create();

        $response = $this->actingAs($user)->get(route('sentence.index'));

        $response->assertStatus(200);
        $response->assertSee('title="' . __('group.visibility.public_unalterable') . '"', false);
    }

    public function test_sentence_index_does_not_display_lock_icon_for_public_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->for($user)->create([
            'visibility' => Group::VISIBILITY_PUBLIC,
        ]);
        Sentence::factory()->for($user)->for($group)->create();

        $response = $this->actingAs($user)->get(route('sentence.index'));

        $response->assertStatus(200);
        $response->assertDontSee('title="' . __('group.visibility.public_unalterable') . '"', false);
    }

    public function test_sentence_add_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('sentence.add'));

        $response->assertStatus(200);
    }

    public function test_sentence_add_preselects_group_from_query_parameter(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('sentence.add', ['group' => $group->id]));

        $response->assertStatus(200);
        $response->assertSee('value="' . $group->id . '" selected', false);
    }

    public function test_sentence_do_add_creates_sentence_and_redirects_to_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('sentence.add.do'), [
            'name' => 'Test sentence',
            'group' => $group->id,
        ]);

        $response->assertRedirect(route('sentence.index', ['group' => $group->id]));
        $this->assertDatabaseHas('sentences', [
            'sentence' => 'Test sentence',
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_add_sentence_to_public_unalterable_group_of_another_user(): void
    {
        $userX = User::factory()->create(['id' => 2]);
        $userY = User::factory()->create(['id' => 3]);
        $group = Group::factory()->for($userX)->create([
            'visibility' => Group::VISIBILITY_PUBLIC_UNALTERABLE,
        ]);

        $response = $this->actingAs($userY)->post(route('sentence.add.do'), [
            'name' => 'Unauthorized sentence',
            'group' => $group->id,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('sentences', [
            'sentence' => 'Unauthorized sentence',
            'group_id' => $group->id,
        ]);
    }

    public function test_owner_can_add_sentence_to_their_public_unalterable_group(): void
    {
        $userX = User::factory()->create(['id' => 2]);
        $group = Group::factory()->for($userX)->create([
            'visibility' => Group::VISIBILITY_PUBLIC_UNALTERABLE,
        ]);

        $response = $this->actingAs($userX)->post(route('sentence.add.do'), [
            'name' => 'Owner sentence',
            'group' => $group->id,
        ]);

        $response->assertRedirect(route('sentence.index', ['group' => $group->id]));
        $this->assertDatabaseHas('sentences', [
            'sentence' => 'Owner sentence',
            'group_id' => $group->id,
            'user_id' => $userX->id,
        ]);
    }

    public function test_admin_can_add_sentence_to_public_unalterable_group_of_another_user(): void
    {
        $admin = User::factory()->create(['id' => 1]);
        $userX = User::factory()->create(['id' => 2]);
        $group = Group::factory()->for($userX)->create([
            'visibility' => Group::VISIBILITY_PUBLIC_UNALTERABLE,
        ]);

        $response = $this->actingAs($admin)->post(route('sentence.add.do'), [
            'name' => 'Admin sentence',
            'group' => $group->id,
        ]);

        $response->assertRedirect(route('sentence.index', ['group' => $group->id]));
        $this->assertDatabaseHas('sentences', [
            'sentence' => 'Admin sentence',
            'group_id' => $group->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_public_unalterable_group_of_another_user_not_listed_in_add_form(): void
    {
        $userX = User::factory()->create(['id' => 2]);
        $userY = User::factory()->create(['id' => 3]);
        $group = Group::factory()->for($userX)->create([
            'name' => 'Unalterable Group',
            'visibility' => Group::VISIBILITY_PUBLIC_UNALTERABLE,
        ]);

        $response = $this->actingAs($userY)->get(route('sentence.add'));

        $response->assertStatus(200);
        $response->assertDontSee('Unalterable Group');
    }

    public function test_public_unalterable_group_does_not_show_add_button_for_another_user_on_index(): void
    {
        $userX = User::factory()->create(['id' => 2]);
        $userY = User::factory()->create(['id' => 3]);
        $group = Group::factory()->for($userX)->create([
            'visibility' => Group::VISIBILITY_PUBLIC_UNALTERABLE,
        ]);

        $response = $this->actingAs($userY)->get(route('sentence.index', ['group' => $group->id]));

        $response->assertStatus(200);
        $response->assertDontSee(route('sentence.add', ['group' => $group->id]));
    }

    public function test_public_unalterable_group_shows_add_button_for_owner_on_index(): void
    {
        $userX = User::factory()->create(['id' => 2]);
        $group = Group::factory()->for($userX)->create([
            'visibility' => Group::VISIBILITY_PUBLIC_UNALTERABLE,
        ]);

        $response = $this->actingAs($userX)->get(route('sentence.index', ['group' => $group->id]));

        $response->assertStatus(200);
        $response->assertSee(route('sentence.add', ['group' => $group->id]));
    }

    public function test_public_unalterable_group_shows_add_button_for_admin_on_index(): void
    {
        $admin = User::factory()->create(['id' => 1]);
        $userX = User::factory()->create(['id' => 2]);
        $group = Group::factory()->for($userX)->create([
            'visibility' => Group::VISIBILITY_PUBLIC_UNALTERABLE,
        ]);

        $response = $this->actingAs($admin)->get(route('sentence.index', ['group' => $group->id]));

        $response->assertStatus(200);
        $response->assertSee(route('sentence.add', ['group' => $group->id]));
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

    public function test_group_index_displays_lock_icon_for_public_unalterable_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->for($user)->create([
            'name' => 'Lock Group',
            'visibility' => Group::VISIBILITY_PUBLIC_UNALTERABLE,
        ]);

        $response = $this->actingAs($user)->get(route('group.index'));

        $response->assertStatus(200);
        $response->assertSee('title="' . __('group.visibility.public_unalterable') . '"', false);
    }

    public function test_group_index_does_not_display_lock_icon_for_public_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->for($user)->create([
            'name' => 'Open Group',
            'visibility' => Group::VISIBILITY_PUBLIC,
        ]);

        $response = $this->actingAs($user)->get(route('group.index'));

        $response->assertStatus(200);
        $response->assertDontSee('title="' . __('group.visibility.public_unalterable') . '"', false);
    }

    public function test_group_add_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('group.add'));

        $response->assertStatus(200);
    }
}
