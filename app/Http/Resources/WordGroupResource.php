<?php

namespace App\Http\Resources;

use App\Models\WordGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WordGroup
 */
class WordGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array. `can` tells the SPA which actions the current user may perform;
     * `creator`, `words_count` and `is_favorite` are only included when loaded; `is_accessible` only in the
     * favorites list, where groups that became private are flagged with `false`; `can.remove_from_collection`
     * only when listed through a collection.
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
            'words_count' => $this->whenCounted('words'),
            'is_favorite' => $this->whenHas('is_favorite', fn (mixed $isFavorite): bool => (bool) $isFavorite),
            'is_accessible' => $this->whenHas('is_accessible'),
            'access_status' => $this->whenHas('access_status'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'can' => [
                'update' => $request->user()->can('update', $this->resource),
                'delete' => $request->user()->can('delete', $this->resource),
                'share' => $request->user()->can('share', $this->resource),
                'manage_access' => $request->user()->can('manageAccess', $this->resource),
                'add_word' => $request->user()->can('addWord', $this->resource),
                'duplicate' => $request->user()->can('duplicate', $this->resource),
                'remove_from_collection' => $this->whenPivotLoaded('groups_collections', fn (): bool => $request->user()
                    ->can('detachWordGroup', [$this->pivot->pivotParent, $this->resource])),
            ],
        ];
    }
}
