<?php

namespace App\Models\Concerns;

use App\Enums\AccessRequestStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared behavior of the requests to access a private group or collection, or to contribute to a public
 * one open to its members only.
 * The using model defines an `item()` relation to the requested group or collection.
 *
 * @property AccessRequestStatus $status
 * @property string $user_id
 */
trait IsAccessRequest
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', AccessRequestStatus::Pending);
    }

    public function isPending(): bool
    {
        return $this->status === AccessRequestStatus::Pending;
    }

    public function isAccepted(): bool
    {
        return $this->status === AccessRequestStatus::Accepted;
    }

    /**
     * Whether the request is about contributing to a public item open to its members only, rather than
     * seeing a private item (judged on the item as it is now).
     */
    public function isForContribution(): bool
    {
        return $this->item->restrictsContributionsToMembers();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AccessRequestStatus::class,
            'decided_at' => 'datetime',
        ];
    }
}
