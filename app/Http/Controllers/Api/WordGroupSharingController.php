<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ManagesSharing;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Sharing of a private word group, for its creator.
 */
class WordGroupSharingController extends Controller
{
    use ManagesSharing;

    /**
     * The access request link, generated on first use.
     */
    public function shareLink(WordGroup $wordGroup): JsonResponse
    {
        return $this->shareLinkResponse($wordGroup, regenerate: false);
    }

    /**
     * Replaces the access request link; the previous one stops working.
     */
    public function regenerateShareLink(WordGroup $wordGroup): JsonResponse
    {
        return $this->shareLinkResponse($wordGroup, regenerate: true);
    }

    public function access(WordGroup $wordGroup): JsonResponse
    {
        return $this->accessResponse($wordGroup);
    }

    public function revoke(WordGroup $wordGroup, User $user): Response
    {
        return $this->revokeResponse($wordGroup, $user);
    }

    protected function sharePathPrefix(): string
    {
        return '/share/groups';
    }
}
