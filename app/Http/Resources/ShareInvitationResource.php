<?php

namespace App\Http\Resources;

use App\Models\GroupCollection;
use App\Models\WordGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the user sees when opening a share link: the item's name and creator, and where they stand:
 * `accessible` (with the item's id to open it), `pending`, `refused` or `none`.
 *
 * @mixin WordGroup|GroupCollection
 */
class ShareInvitationResource extends JsonResource
{
    /**
     * Transform the resource into an array. Expects the `access_status` attribute to be set.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kind' => $this->resource instanceof WordGroup ? 'word-groups' : 'group-collections',
            'id' => $this->when($this->access_status === 'accessible', $this->id),
            'name' => $this->name,
            'creator' => ['name' => $this->creator->name],
            'status' => $this->access_status,
        ];
    }
}
