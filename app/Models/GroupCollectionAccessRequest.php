<?php

namespace App\Models;

use App\Models\Concerns\IsAccessRequest;
use Database\Factories\GroupCollectionAccessRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's request to access a private collection, sent from its share link.
 */
#[Fillable(['status', 'decided_at'])]
class GroupCollectionAccessRequest extends Model
{
    /** @use HasFactory<GroupCollectionAccessRequestFactory> */
    use HasFactory, HasUuids, IsAccessRequest;

    /**
     * @return BelongsTo<GroupCollection, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(GroupCollection::class, 'group_collection_id');
    }

    /**
     * Names the requested item after "accès", e.g. "à la collection « Soirée jeux »".
     */
    public function accessTarget(): string
    {
        return __('access_requests.target.group_collection', ['name' => $this->item->name]);
    }

    /**
     * Path of the requested item's page in the SPA.
     */
    public function itemPath(): string
    {
        return "/collections/{$this->group_collection_id}";
    }
}
