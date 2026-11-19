<?php

namespace App\Http\Resources;

use App\Models\GroupCollectionAccessRequest;
use App\Models\WordGroupAccessRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An access request as seen by the creator of the requested item. Expects `item` and `requester` loaded.
 * `purpose` is `contribution` when the item is public and open to its members only, `access` otherwise.
 *
 * @mixin WordGroupAccessRequest|GroupCollectionAccessRequest
 */
class AccessRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isWordGroupRequest = $this->resource instanceof WordGroupAccessRequest;

        return [
            'id' => $this->id,
            'kind' => $isWordGroupRequest ? 'word-groups' : 'group-collections',
            'item' => [
                'id' => $this->item->id,
                'name' => $this->item->name,
            ],
            'requester' => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
            ],
            'purpose' => $this->isForContribution() ? 'contribution' : 'access',
            'via_group_collection' => $isWordGroupRequest && $this->via_group_collection_id !== null
                ? $this->whenLoaded('viaGroupCollection', fn (): ?array => $this->viaGroupCollection ? [
                    'id' => $this->viaGroupCollection->id,
                    'name' => $this->viaGroupCollection->name,
                ] : null)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
