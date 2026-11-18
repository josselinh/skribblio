<?php

namespace App\Models;

use Database\Factories\WordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A word belongs to exactly one group and has no visibility of its own:
 * it is visible to whoever can see its group. Adding, renaming or deleting
 * a word updates the group's `updated_at`.
 */
#[Fillable(['name'])]
#[Touches(['wordGroup'])]
class Word extends Model
{
    /** @use HasFactory<WordFactory> */
    use HasFactory, HasUuids;

    /**
     * Longest word that Skribbl.io accepts in a custom word list.
     */
    public const int NAME_MAX_LENGTH = 32;

    /**
     * @return BelongsTo<WordGroup, $this>
     */
    public function wordGroup(): BelongsTo
    {
        return $this->belongsTo(WordGroup::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCreatedBy(User $user): bool
    {
        return $this->created_by === $user->getKey();
    }
}
