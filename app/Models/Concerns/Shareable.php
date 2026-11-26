<?php

namespace App\Models\Concerns;

use App\Enums\AccessRequestStatus;
use App\Enums\ShareType;
use App\Models\GroupCollectionAccessRequest;
use App\Models\User;
use App\Models\WordGroupAccessRequest;
use App\Notifications\AccessRequestDecided;
use App\Notifications\AccessRequested;
use App\Notifications\ShareTypeChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sharing of a private group or collection, or of a public one whose contributions are restricted to its
 * members: a secret link lets other users request access (to see the item, or to contribute to it), the
 * creator accepts (the requester becomes a member, with a partial or full sharing, see ShareType) or refuses,
 * and can change the type of sharing or revoke access later. The using
 * model also uses HasVisibility and AcceptsContributions, and defines `members()` and `accessRequests()`
 * relations.
 *
 * @property ?string $share_token
 */
trait Shareable
{
    /**
     * Per-instance cache of shareTypeOf(), by user id (null for a non-member).
     *
     * @var array<string, ?ShareType>
     */
    private array $shareTypeCache = [];

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
     * Whether the user already has what the share link gives: contributing to a public item open to its
     * members only (being its creator or a member), seeing the item otherwise.
     */
    public function hasAccess(User $user): bool
    {
        return $this->restrictsContributionsToMembers()
            ? $this->isCreatedBy($user) || $this->isMember($user)
            : $this->isVisibleTo($user);
    }

    /**
     * Where the user's request stands: `pending`, `refused`, or `none` (no request, or an accepted one whose
     * access was revoked since).
     */
    public function accessRequestStatusOf(User $user): string
    {
        return match ($this->accessRequests()->where('user_id', $user->getKey())->first()?->status) {
            AccessRequestStatus::Pending => 'pending',
            AccessRequestStatus::Refused => 'refused',
            default => 'none',
        };
    }

    /**
     * Records a pending access request from the user and notifies the creator. Returns null when the user
     * already has access (see hasAccess()). Asking again while a request is pending does nothing; asking again after a
     * refusal sends a new request.
     *
     * @param  array<string, mixed>  $attributes  Extra attributes of the request (e.g. `via_group_collection_id`).
     */
    public function requestAccess(User $user, array $attributes = []): WordGroupAccessRequest|GroupCollectionAccessRequest|null
    {
        if ($this->hasAccess($user)) {
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
     * Grants access to the requester, with the given type of sharing, and notifies them.
     */
    public function acceptAccessRequest(WordGroupAccessRequest|GroupCollectionAccessRequest $accessRequest, ShareType $shareType = ShareType::Partial): void
    {
        DB::transaction(function () use ($accessRequest, $shareType): void {
            $this->members()->syncWithoutDetaching([$accessRequest->user_id => ['share_type' => $shareType->value]]);
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
     * The type of sharing of a member (accepted through the share link), null for anyone else.
     */
    public function shareTypeOf(User $user): ?ShareType
    {
        if (! array_key_exists($user->getKey(), $this->shareTypeCache)) {
            $shareType = $this->members()->whereKey($user->getKey())->first()?->pivot->share_type;
            $this->shareTypeCache[$user->getKey()] = $shareType === null ? null : ShareType::from($shareType);
        }

        return $this->shareTypeCache[$user->getKey()];
    }

    /**
     * Whether the item can be shared through its link: open to contributions, and private or public open to its
     * members only. Otherwise its members have no access left (a private item closed to contributions is only
     * visible to its creator), and they get it back when the item becomes shareable again.
     */
    public function isShareable(): bool
    {
        return $this->accepts_contributions && ($this->isPrivate() || $this->contributions_members_only);
    }

    /**
     * Whether the user is a member with a full sharing that still applies (the item is shareable).
     */
    public function sharesFullyWith(User $user): bool
    {
        return $this->isShareable() && $this->shareTypeOf($user) === ShareType::Full;
    }

    /**
     * Changes the type of sharing of a member and notifies them. Returns false, without notifying, when the user
     * is not a member or already had that type.
     */
    public function changeShareType(User $member, ShareType $shareType): bool
    {
        $currentShareType = $this->shareTypeOf($member);
        if ($currentShareType === null || $currentShareType === $shareType) {
            return false;
        }

        $this->members()->updateExistingPivot($member->getKey(), ['share_type' => $shareType->value]);
        $this->shareTypeCache[$member->getKey()] = $shareType;
        $member->notify(new ShareTypeChanged($this, $shareType));

        return true;
    }

    /**
     * Removes the user's access and their request, so that they can ask again through the share link.
     */
    public function revokeAccess(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->members()->detach($user);
            unset($this->shareTypeCache[$user->getKey()]);
            $this->accessRequests()->where('user_id', $user->getKey())->delete();
        });
    }

    /**
     * The user, a member, gives up their access, as if the creator had revoked it. Their favorites are kept:
     * the favorites lists leave out what they can no longer see, and show it again if they regain access.
     */
    public function leave(User $user): void
    {
        $this->revokeAccess($user);
    }

    /**
     * Hides the share token from serialization.
     */
    protected function initializeShareable(): void
    {
        $this->makeHidden('share_token');
    }
}
