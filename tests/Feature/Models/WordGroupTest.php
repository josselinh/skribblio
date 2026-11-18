<?php

namespace Tests\Feature\Models;

use App\Models\GroupCollection;
use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WordGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_visible_to_returns_public_groups_and_own_private_groups_only(): void
    {
        $user = User::factory()->create();
        $ownPrivateGroup = WordGroup::factory()->private()->createdBy($user)->create();
        $publicGroup = WordGroup::factory()->public()->create();
        WordGroup::factory()->private()->create();

        $visibleIds = WordGroup::visibleTo($user)->pluck('id');

        $this->assertEqualsCanonicalizing([$ownPrivateGroup->id, $publicGroup->id], $visibleIds->all());
    }

    public function test_uses_uuid_primary_key(): void
    {
        $this->assertTrue(Str::isUuid(WordGroup::factory()->create()->id));
    }

    public function test_rejects_duplicate_word_name_within_the_same_group(): void
    {
        $wordGroup = WordGroup::factory()->create();
        Word::factory()->for($wordGroup)->create(['name' => 'chat']);

        $this->expectException(UniqueConstraintViolationException::class);

        Word::factory()->for($wordGroup)->create(['name' => 'chat']);
    }

    public function test_allows_same_word_name_in_different_groups(): void
    {
        Word::factory()->create(['name' => 'chat']);
        Word::factory()->create(['name' => 'chat']);

        $this->assertSame(2, Word::where('name', 'chat')->count());
    }

    public function test_deleting_group_deletes_its_words_and_detaches_it_from_collections(): void
    {
        $wordGroup = WordGroup::factory()->create();
        $word = Word::factory()->for($wordGroup)->create();
        $groupCollection = GroupCollection::factory()->hasAttached([$wordGroup, WordGroup::factory()->create()])->create();

        $wordGroup->delete();

        $this->assertModelMissing($word);
        $this->assertModelExists($groupCollection);
        $this->assertDatabaseMissing('groups_collections', ['word_group_id' => $wordGroup->id]);
    }
}
