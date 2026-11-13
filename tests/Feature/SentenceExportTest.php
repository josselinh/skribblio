<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Sentence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SentenceExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private User $otherUser;

    private Group $publicGroup;
    private Group $unalterableGroup;
    private Group $userPrivateGroup;
    private Group $otherPrivateGroup;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin first, so that regular users don't get id 1
        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->publicGroup = $this->createGroup('grp-public', Group::VISIBILITY_PUBLIC, $this->otherUser);
        $this->unalterableGroup = $this->createGroup('grp-unalterable', Group::VISIBILITY_PUBLIC_UNALTERABLE, $this->otherUser);
        $this->userPrivateGroup = $this->createGroup('grp-user-private', Group::VISIBILITY_PRIVATE, $this->user);
        $this->otherPrivateGroup = $this->createGroup('grp-other-private', Group::VISIBILITY_PRIVATE, $this->otherUser);

        $this->createSentence('word-public', $this->publicGroup);
        $this->createSentence('word-unalterable', $this->unalterableGroup);
        $this->createSentence('word-user-private', $this->userPrivateGroup);
        $this->createSentence('word-other-private', $this->otherPrivateGroup);
    }

    // Access and basic display

    public function test_guest_can_access_export_page(): void
    {
        $this->get(route('sentence.export'))->assertOk();
    }

    public function test_result_is_hidden_when_no_group_is_submitted(): void
    {
        $this->get(route('sentence.export'))
            ->assertOk()
            ->assertDontSee(__('sentence.export.result'));
    }

    // Visibility

    public function test_guest_only_sees_public_groups(): void
    {
        $this->get(route('sentence.export'))
            ->assertSee('grp-public')
            ->assertSee('grp-unalterable')
            ->assertDontSee('grp-user-private')
            ->assertDontSee('grp-other-private');
    }

    public function test_guest_cannot_export_private_group_by_forcing_url(): void
    {
        $this->get($this->exportUrl($this->otherPrivateGroup))
            ->assertOk()
            ->assertSee(__('sentence.export.result'))
            ->assertDontSee('word-other-private');
    }

    public function test_user_sees_own_private_groups_but_not_others(): void
    {
        $this->actingAs($this->user)
            ->get(route('sentence.export'))
            ->assertSee('grp-public')
            ->assertSee('grp-unalterable')
            ->assertSee('grp-user-private')
            ->assertDontSee('grp-other-private');
    }

    public function test_user_cannot_export_others_private_group_by_forcing_url(): void
    {
        $this->actingAs($this->user)
            ->get($this->exportUrl($this->userPrivateGroup, $this->otherPrivateGroup))
            ->assertSee('word-user-private')
            ->assertDontSee('word-other-private');
    }

    public function test_admin_sees_all_groups(): void
    {
        $this->actingAs($this->admin)
            ->get(route('sentence.export'))
            ->assertSee('grp-public')
            ->assertSee('grp-unalterable')
            ->assertSee('grp-user-private')
            ->assertSee('grp-other-private');
    }

    public function test_admin_can_export_any_private_group(): void
    {
        $this->actingAs($this->admin)
            ->get($this->exportUrl($this->userPrivateGroup, $this->otherPrivateGroup))
            ->assertSee('word-user-private')
            ->assertSee('word-other-private');
    }

    // Export content

    public function test_only_selected_groups_are_exported(): void
    {
        $this->get($this->exportUrl($this->publicGroup))
            ->assertSee('word-public')
            ->assertDontSee('word-unalterable');
    }

    public function test_only_sentences_with_positive_or_zero_note_are_exported(): void
    {
        $this->createSentence('word-zero', $this->publicGroup, 0);
        $this->createSentence('word-negative', $this->publicGroup, -1);

        $this->get($this->exportUrl($this->publicGroup))
            ->assertSee('word-zero')
            ->assertDontSee('word-negative');
    }

    public function test_duplicate_sentences_are_exported_once(): void
    {
        $this->createSentence('word-shared', $this->publicGroup);
        $this->createSentence('word-shared', $this->unalterableGroup);

        $content = $this->get($this->exportUrl($this->publicGroup, $this->unalterableGroup))->getContent();

        $this->assertSame(1, substr_count($content, 'word-shared'));
    }

    public function test_sentences_are_exported_as_comma_separated_list(): void
    {
        // Sentences are ordered by created_at desc: force distinct dates
        $this->createSentence('word-old', $this->publicGroup, 0, now()->subDay());

        $this->get($this->exportUrl($this->publicGroup))
            ->assertSee('word-public, word-old');
    }

    // Invalid input

    public function test_export_with_non_array_groups_is_rejected(): void
    {
        $this->get(route('sentence.export', ['groups' => 'abc']))
            ->assertRedirect(route('sentence.export'))
            ->assertSessionHasErrors('groups');
    }

    public function test_export_with_non_integer_group_ids_is_rejected(): void
    {
        $this->get(route('sentence.export', ['groups' => ['abc']]))
            ->assertRedirect(route('sentence.export'))
            ->assertSessionHasErrors('groups.0');
    }

    // Helpers

    private function createGroup(string $name, int $visibility, User $owner): Group
    {
        return Group::factory()->create([
            'name' => $name,
            'visibility' => $visibility,
            'user_id' => $owner->id,
        ]);
    }

    private function createSentence(string $sentence, Group $group, int $note = 1, $createdAt = null): Sentence
    {
        return Sentence::factory()->create([
            'sentence' => $sentence,
            'note' => $note,
            'group_id' => $group->id,
            'user_id' => $group->user_id,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    private function exportUrl(Group ...$groups): string
    {
        return route('sentence.export', ['groups' => array_map(fn (Group $group) => $group->id, $groups)]);
    }
}
