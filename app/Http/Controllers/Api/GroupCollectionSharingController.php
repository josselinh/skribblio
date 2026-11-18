<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ManagesSharing;
use App\Http\Controllers\Controller;
use App\Models\GroupCollection;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Sharing of a private collection, for its creator, and access requests to the groups of a collection
 * that are hidden from its viewer.
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

    public function revoke(GroupCollection $groupCollection, User $user): Response
    {
        return $this->revokeResponse($groupCollection, $user);
    }

    /**
     * Requests access to every group of the collection the user cannot see, each request going to the
     * group's creator. The user does not learn the groups' names.
     */
    public function requestHiddenWordGroups(Request $request, GroupCollection $groupCollection): JsonResponse
    {
        Gate::authorize('view', $groupCollection);
        $user = $request->user();

        $hiddenWordGroups = $groupCollection->wordGroups()
            ->whereNotIn('word_groups.id', $groupCollection->wordGroups()->visibleTo($user)->select('word_groups.id'))
            ->with('creator')
            ->get();

        foreach ($hiddenWordGroups as $wordGroup) {
            $wordGroup->requestAccess($user, ['via_group_collection_id' => $groupCollection->getKey()]);
        }

        return response()->json(['data' => ['requested_count' => $hiddenWordGroups->count()]]);
    }

    protected function sharePathPrefix(): string
    {
        return '/share/collections';
    }
}
