<?php

namespace App\Http\Resources;

use App\Models\WordGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin WordGroup
 */
class WordGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array. `can` tells the SPA which actions the current user may perform;
     * `creator` (never for guests: the names of the users are only shown to logged-in users), `words_count` and `is_favorite` are only included when loaded; `is_accessible` only in the
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
            'language' => $this->language->value,
            'visibility' => $this->visibility->value,
            'accepts_contributions' => $this->accepts_contributions,
            'contributions_members_only' => $this->contributions_members_only,
            'creator' => $this->when($request->user() !== null, fn (): mixed => $this->whenLoaded('creator', fn (): array => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ])),
            'words_count' => $this->whenCounted('words'),
            'is_favorite' => $this->whenHas('is_favorite', fn (mixed $isFavorite): bool => (bool) $isFavorite),
            'is_accessible' => $this->whenHas('is_accessible'),
            'access_status' => $this->whenHas('access_status'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'can' => [
                'update' => Gate::allows('update', $this->resource),
                'delete' => Gate::allows('delete', $this->resource),
                'share' => Gate::allows('share', $this->resource),
                'manage_access' => Gate::allows('manageAccess', $this->resource),
                'leave' => Gate::allows('leave', $this->resource),
                'add_word' => Gate::allows('addWord', $this->resource),
                'duplicate' => Gate::allows('duplicate', $this->resource),
                'remove_from_collection' => $this->whenPivotLoaded('groups_collections', fn (): bool => Gate::allows('detachWordGroup', [$this->pivot->pivotParent, $this->resource])),
            ],
        ];
    }
}
