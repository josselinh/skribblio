<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccessRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ShareInvitationResource;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Http\Request;

/**
 * Share links, as opened by the people they were sent to: they see what is shared and can request access.
 * An unknown or regenerated token answers 404.
 */
class ShareInvitationController extends Controller
{
    public function showWordGroup(Request $request, WordGroup $wordGroup): ShareInvitationResource
    {
        return $this->invitation($wordGroup, $request->user());
    }

    public function requestWordGroup(Request $request, WordGroup $wordGroup): ShareInvitationResource
    {
        $wordGroup->requestAccess($request->user());

        return $this->invitation($wordGroup, $request->user());
    }

    public function showGroupCollection(Request $request, GroupCollection $groupCollection): ShareInvitationResource
    {
        return $this->invitation($groupCollection, $request->user());
    }

    public function requestGroupCollection(Request $request, GroupCollection $groupCollection): ShareInvitationResource
    {
        $groupCollection->requestAccess($request->user());

        return $this->invitation($groupCollection, $request->user());
    }

    private function invitation(WordGroup|GroupCollection $item, User $user): ShareInvitationResource
    {
        $status = match (true) {
            $item->isVisibleTo($user) => 'accessible',
            default => match ($item->accessRequests()->where('user_id', $user->getKey())->first()?->status) {
                AccessRequestStatus::Pending => 'pending',
                AccessRequestStatus::Refused => 'refused',
                default => 'none',
            },
        };

        return new ShareInvitationResource($item->load('creator')->setAttribute('access_status', $status));
    }
}
