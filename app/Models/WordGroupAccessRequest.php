<?php

namespace App\Models;

use App\Models\Concerns\IsAccessRequest;
use Database\Factories\WordGroupAccessRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's request to access a private word group, sent from its share link or from a collection
 * containing it (`via_group_collection_id`; the requester then does not know the group's name).
 */
#[Fillable(['status', 'decided_at', 'via_group_collection_id'])]
class WordGroupAccessRequest extends Model
{
    /** @use HasFactory<WordGroupAccessRequestFactory> */
    use HasFactory, HasUuids, IsAccessRequest;

    /**
     * @return BelongsTo<WordGroup, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(WordGroup::class, 'word_group_id');
    }

    /**
     * The collection from which the request was sent, if any.
     *
     * @return BelongsTo<GroupCollection, $this>
     */
    public function viaGroupCollection(): BelongsTo
    {
        return $this->belongsTo(GroupCollection::class, 'via_group_collection_id');
    }

    /**
     * Names the requested item after "accès", e.g. "au groupe « Animaux »".
     */
    public function accessTarget(): string
    {
        return __('access_requests.target.word_group', ['name' => $this->item->name]);
    }

    /**
     * Path of the requested item's page in the SPA.
     */
    public function itemPath(): string
    {
        return "/groups/{$this->word_group_id}";
    }
}
