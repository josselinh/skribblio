<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Http\Requests\UpdateMemberRequest;
use App\Http\Resources\AccessRequestResource;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Share link and access management (requests, members and their type of sharing) of a private group or collection, for its creator; leaving it, for its members.
 */
trait ManagesSharing
{
    /**
     * Path prefix of the share page in the SPA, e.g. "/share/groups".
     */
    abstract protected function sharePathPrefix(): string;

    protected function shareLinkResponse(WordGroup|GroupCollection $item, bool $regenerate): JsonResponse
    {
        Gate::authorize('share', $item);

        $token = $regenerate ? $item->regenerateShareToken() : $item->ensureShareToken();

        return response()->json(['data' => ['url' => url("{$this->sharePathPrefix()}/{$token}")]]);
    }

    /**
     * Pending access requests, oldest first, and the users granted access, most recent first.
     */
    protected function accessResponse(WordGroup|GroupCollection $item): JsonResponse
    {
        Gate::authorize('manageAccess', $item);

        $pendingRequests = $item->accessRequests()->pending()->with('requester')->oldest()->get();
        $members = $item->members()->orderByPivot('created_at', 'desc')->get();

        return response()->json(['data' => [
            'pending_requests' => AccessRequestResource::collection($pendingRequests->each->setRelation('item', $item)),
            'members' => $members->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'share_type' => $member->pivot->share_type,
                'granted_at' => $member->pivot->created_at?->toIso8601String(),
            ]),
        ]]);
    }

    /**
     * Changes the type of sharing of a member (404 when the user is not one); the member is notified.
     */
    protected function updateMemberResponse(UpdateMemberRequest $request): JsonResponse
    {
        $item = $request->item();
        $member = $request->member();
        abort_if($item->shareTypeOf($member) === null, 404);

        $item->changeShareType($member, $request->shareType());

        return response()->json(['data' => [
            'id' => $member->id,
            'name' => $member->name,
            'share_type' => $item->shareTypeOf($member)->value,
        ]]);
    }

    protected function revokeResponse(WordGroup|GroupCollection $item, User $user): Response
    {
        Gate::authorize('manageAccess', $item);

        $item->revokeAccess($user);

        return response()->noContent();
    }

    /**
     * The current user gives up their membership, as if the creator had revoked it (they can ask again
     * through the share link), and loses their favorites on what they can no longer see.
     */
    protected function leaveResponse(WordGroup|GroupCollection $item, User $user): Response
    {
        Gate::authorize('leave', $item);

        $item->leave($user);

        return response()->noContent();
    }
}
