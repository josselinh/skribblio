<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShareInvitationResource;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Http\Request;

/**
 * Share links, as opened by the people they were sent to: they see what is shared and can request access
 * (to see a private item, or to contribute to a public item open to its members only).
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
        $status = $item->hasAccess($user) ? 'accessible' : $item->accessRequestStatusOf($user);

        return new ShareInvitationResource($item->load('creator')->forceFill([
            'access_status' => $status,
            'is_visible' => $item->isVisibleTo($user),
        ]));
    }
}
