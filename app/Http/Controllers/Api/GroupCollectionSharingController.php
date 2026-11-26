<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ManagesSharing;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\GroupCollection;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Sharing of a private collection, for its creator.
 */
class GroupCollectionSharingController extends Controller
{
    use ManagesSharing;

    /**
     * The access request link, generated on first use.
     */
    public function shareLink(GroupCollection $groupCollection): JsonResponse
    {
        return $this->shareLinkResponse($groupCollection, regenerate: false);
    }

    /**
     * Replaces the access request link; the previous one stops working.
     */
    public function regenerateShareLink(GroupCollection $groupCollection): JsonResponse
    {
        return $this->shareLinkResponse($groupCollection, regenerate: true);
    }

    public function access(GroupCollection $groupCollection): JsonResponse
    {
        return $this->accessResponse($groupCollection);
    }

    /**
     * Changes the type of sharing of a member.
     */
    public function updateMember(UpdateMemberRequest $request, GroupCollection $groupCollection, User $user): JsonResponse
    {
        return $this->updateMemberResponse($request);
    }

    public function revoke(GroupCollection $groupCollection, User $user): Response
    {
        return $this->revokeResponse($groupCollection, $user);
    }

    /**
     * The current user, a member, leaves the item.
     */
    public function leave(Request $request, GroupCollection $groupCollection): Response
    {
        return $this->leaveResponse($groupCollection, $request->user());
    }

    protected function sharePathPrefix(): string
    {
        return '/share/collections';
    }
}
