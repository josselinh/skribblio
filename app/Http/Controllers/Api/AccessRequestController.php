<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccessRequestResource;
use App\Models\GroupCollectionAccessRequest;
use App\Models\WordGroupAccessRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Access requests received by the creator of private groups and collections, and their decisions.
 */
class AccessRequestController extends Controller
{
    /**
     * Pending requests on the current user's groups and collections, most recent first.
     */
    public function received(Request $request): AnonymousResourceCollection
    {
        $createdByUser = fn (Builder $query) => $query->where('created_by', $request->user()->getKey());

        $accessRequests = WordGroupAccessRequest::pending()
            ->whereHas('item', $createdByUser)
            ->with(['item', 'requester', 'viaGroupCollection'])
            ->get()
            ->concat(GroupCollectionAccessRequest::pending()
                ->whereHas('item', $createdByUser)
                ->with(['item', 'requester'])
                ->get())
            ->sortByDesc('created_at')
            ->values();

        return AccessRequestResource::collection($accessRequests);
    }

    public function acceptWordGroup(WordGroupAccessRequest $wordGroupAccessRequest): Response
    {
        return $this->decide($wordGroupAccessRequest, accept: true);
    }

    public function refuseWordGroup(WordGroupAccessRequest $wordGroupAccessRequest): Response
    {
        return $this->decide($wordGroupAccessRequest, accept: false);
    }

    public function acceptGroupCollection(GroupCollectionAccessRequest $groupCollectionAccessRequest): Response
    {
        return $this->decide($groupCollectionAccessRequest, accept: true);
    }

    public function refuseGroupCollection(GroupCollectionAccessRequest $groupCollectionAccessRequest): Response
    {
        return $this->decide($groupCollectionAccessRequest, accept: false);
    }

    /**
     * @throws ValidationException
     */
    private function decide(WordGroupAccessRequest|GroupCollectionAccessRequest $accessRequest, bool $accept): Response
    {
        Gate::authorize('manageAccess', $accessRequest->item);

        if (! $accessRequest->isPending()) {
            throw ValidationException::withMessages(['status' => 'Cette demande a déjà été traitée.']);
        }

        $accept
            ? $accessRequest->item->acceptAccessRequest($accessRequest)
            : $accessRequest->item->refuseAccessRequest($accessRequest);

        return response()->noContent();
    }
}
