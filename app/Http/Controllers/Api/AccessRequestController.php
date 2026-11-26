<?php

namespace App\Http\Controllers\Api;

use App\Enums\ShareType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptAccessRequestRequest;
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
 * Access requests received by the creator of private groups and collections, and their decisions (an
 * acceptance grants a partial sharing unless `share_type` says otherwise).
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

    public function acceptWordGroup(AcceptAccessRequestRequest $request, WordGroupAccessRequest $wordGroupAccessRequest): Response
    {
        return $this->decide($wordGroupAccessRequest, accept: true, shareType: $request->shareType());
    }

    public function refuseWordGroup(WordGroupAccessRequest $wordGroupAccessRequest): Response
    {
        return $this->decide($wordGroupAccessRequest, accept: false);
    }

    public function acceptGroupCollection(AcceptAccessRequestRequest $request, GroupCollectionAccessRequest $groupCollectionAccessRequest): Response
    {
        return $this->decide($groupCollectionAccessRequest, accept: true, shareType: $request->shareType());
    }

    public function refuseGroupCollection(GroupCollectionAccessRequest $groupCollectionAccessRequest): Response
    {
        return $this->decide($groupCollectionAccessRequest, accept: false);
    }

    /**
     * @throws ValidationException
     */
    private function decide(WordGroupAccessRequest|GroupCollectionAccessRequest $accessRequest, bool $accept, ShareType $shareType = ShareType::Partial): Response
    {
        Gate::authorize('manageAccess', $accessRequest->item);

        if (! $accessRequest->isPending()) {
            throw ValidationException::withMessages(['status' => __('access_requests.already_decided')]);
        }
        if ($accept && ! $accessRequest->item->isShareable()) {
            $kind = $accessRequest instanceof WordGroupAccessRequest ? 'word_group' : 'group_collection';
            throw ValidationException::withMessages(['status' => __("access_requests.not_shareable.{$kind}")]);
        }

        $accept
            ? $accessRequest->item->acceptAccessRequest($accessRequest, $shareType)
            : $accessRequest->item->refuseAccessRequest($accessRequest);

        return response()->noContent();
    }
}
