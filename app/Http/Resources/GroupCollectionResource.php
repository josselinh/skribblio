<?php

namespace App\Http\Resources;

use App\Models\GroupCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GroupCollection
 */
class GroupCollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array. `can` tells the SPA which actions the current user may perform.
     * `word_groups` (only the groups the user can see) and `hidden_word_groups_count` are only included
     * on the detail endpoint; `creator` and `word_groups_count` only when loaded (search).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'visibility' => $this->visibility->value,
            'creator' => $this->whenLoaded('creator', fn (): array => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'word_groups_count' => $this->whenCounted('wordGroups'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'word_groups' => WordGroupResource::collection($this->whenLoaded('wordGroups')),
            'hidden_word_groups_count' => $this->whenHas('hidden_word_groups_count'),
            'can' => [
                'update' => $request->user()->can('update', $this->resource),
            ],
        ];
    }
}
