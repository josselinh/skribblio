<?php

namespace App\Http\Resources;

use App\Models\GroupCollection;
use App\Models\WordGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the user sees when opening a share link: the item's name and creator, what the link gives
 * (`purpose`: `access` to a private item, `contribution` to a public item open to its members only),
 * and where they stand: `accessible` (they already have it), `pending`, `refused` or `none`. The item's
 * id, to open it, is given whenever the user can see the item.
 *
 * @mixin WordGroup|GroupCollection
 */
class ShareInvitationResource extends JsonResource
{
    /**
     * Transform the resource into an array. Expects the `access_status` and `is_visible` attributes to be set.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kind' => $this->resource instanceof WordGroup ? 'word-groups' : 'group-collections',
            'id' => $this->when($this->is_visible, $this->id),
            'name' => $this->name,
            'creator' => ['name' => $this->creator->name],
            'purpose' => $this->restrictsContributionsToMembers() ? 'contribution' : 'access',
            'status' => $this->access_status,
        ];
    }
}
