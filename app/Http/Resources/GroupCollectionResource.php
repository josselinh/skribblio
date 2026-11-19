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
     * on the detail endpoint; `creator`, `word_groups_count` and `is_favorite` only when loaded; `is_accessible`
     * only in the favorites list, where collections that became private are flagged with `false`.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'visibility' => $this->visibility->value,
            'accepts_contributions' => $this->accepts_contributions,
            'creator' => $this->whenLoaded('creator', fn (): array => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'word_groups_count' => $this->whenCounted('wordGroups'),
            'is_favorite' => $this->whenHas('is_favorite', fn (mixed $isFavorite): bool => (bool) $isFavorite),
            'is_accessible' => $this->whenHas('is_accessible'),
            'access_status' => $this->whenHas('access_status'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'word_groups' => WordGroupResource::collection($this->whenLoaded('wordGroups')),
            'hidden_word_groups_count' => $this->whenHas('hidden_word_groups_count'),
            'hidden_word_groups_requested_count' => $this->whenHas('hidden_word_groups_requested_count'),
            'can' => [
                'update' => $request->user()->can('update', $this->resource),
                'share' => $request->user()->can('share', $this->resource),
                'manage_access' => $request->user()->can('manageAccess', $this->resource),
                'add_word_group' => $request->user()->can('addWordGroup', $this->resource),
            ],
        ];
    }
}
