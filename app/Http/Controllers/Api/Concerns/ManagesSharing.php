<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Http\Resources\AccessRequestResource;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Share link and access management of a private group or collection, for its creator.
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
                'granted_at' => $member->pivot->created_at?->toIso8601String(),
            ]),
        ]]);
    }

    protected function revokeResponse(WordGroup|GroupCollection $item, User $user): Response
    {
        Gate::authorize('manageAccess', $item);

        $item->revokeAccess($user);

        return response()->noContent();
    }
}
