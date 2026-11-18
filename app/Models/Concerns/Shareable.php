<?php

namespace App\Models\Concerns;

use App\Enums\AccessRequestStatus;
use App\Models\GroupCollectionAccessRequest;
use App\Models\User;
use App\Models\WordGroupAccessRequest;
use App\Notifications\AccessRequestDecided;
use App\Notifications\AccessRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sharing of a private group or collection: a secret link lets other users request access, the creator
 * accepts (the requester becomes a member) or refuses, and can revoke access later. The using model
 * also uses HasVisibility and defines `members()` and `accessRequests()` relations.
 *
 * @property ?string $share_token
 */
trait Shareable
{
    /**
     * Returns the share token, generating it on first use. Does not change `updated_at`.
     */
    public function ensureShareToken(): string
    {
        return $this->share_token ?? $this->regenerateShareToken();
    }

    /**
     * Replaces the share token, so that previously shared links stop working. Does not change `updated_at`.
     */
    public function regenerateShareToken(): string
    {
        $token = Str::random(40);
        static::query()->whereKey($this->getKey())->toBase()->update(['share_token' => $token]);
        $this->setRawAttributes([...$this->getAttributes(), 'share_token' => $token], sync: true);

        return $token;
    }

    /**
     * Records a pending access request from the user and notifies the creator. Returns null when the user
     * can already see the item. Asking again while a request is pending does nothing; asking again after a
     * refusal sends a new request.
     *
     * @param  array<string, mixed>  $attributes  Extra attributes of the request (e.g. `via_group_collection_id`).
     */
    public function requestAccess(User $user, array $attributes = []): WordGroupAccessRequest|GroupCollectionAccessRequest|null
    {
        if ($this->isVisibleTo($user)) {
            return null;
        }

        /** @var WordGroupAccessRequest|GroupCollectionAccessRequest $accessRequest */
        $accessRequest = $this->accessRequests()->where('user_id', $user->getKey())->first()
            ?? $this->accessRequests()->make()->forceFill(['user_id' => $user->getKey()]);
        if ($accessRequest->exists && $accessRequest->isPending()) {
            return $accessRequest;
        }

        $accessRequest->fill([...$attributes, 'status' => AccessRequestStatus::Pending, 'decided_at' => null])->save();
        $this->creator->notify(new AccessRequested($accessRequest->setRelation('item', $this)->setRelation('requester', $user)));

        return $accessRequest;
    }

    /**
     * Grants access to the requester and notifies them.
     */
    public function acceptAccessRequest(WordGroupAccessRequest|GroupCollectionAccessRequest $accessRequest): void
    {
        DB::transaction(function () use ($accessRequest): void {
            $this->members()->syncWithoutDetaching([$accessRequest->user_id]);
            $accessRequest->update(['status' => AccessRequestStatus::Accepted, 'decided_at' => now()]);
        });

        $accessRequest->requester->notify(new AccessRequestDecided($accessRequest->setRelation('item', $this)));
    }

    /**
     * Refuses the request and notifies the requester, who may ask again later.
     */
    public function refuseAccessRequest(WordGroupAccessRequest|GroupCollectionAccessRequest $accessRequest): void
    {
        $accessRequest->update(['status' => AccessRequestStatus::Refused, 'decided_at' => now()]);

        $accessRequest->requester->notify(new AccessRequestDecided($accessRequest->setRelation('item', $this)));
    }

    /**
     * Removes the user's access and their request, so that they can ask again through the share link.
     */
    public function revokeAccess(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->members()->detach($user);
            $this->accessRequests()->where('user_id', $user->getKey())->delete();
        });
    }

    /**
     * Hides the share token from serialization.
     */
    protected function initializeShareable(): void
    {
        $this->makeHidden('share_token');
    }
}
