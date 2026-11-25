<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A group's membership of a collection (`groups_collections`), with the user who added it.
 */
class GroupCollectionWordGroup extends Pivot
{
    protected $table = 'groups_collections';

    /**
     * The user who added the group to the collection; null once that user was deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
