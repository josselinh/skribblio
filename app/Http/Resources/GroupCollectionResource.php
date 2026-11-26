<?php

namespace App\Http\Resources;

use App\Models\GroupCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin GroupCollection
 */
class GroupCollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array. `can` tells the SPA which actions the current user may perform.
     * `word_groups` (only the groups the user can see, never telling that others are hidden) is only
     * included on the detail endpoint; `creator` (never for guests: the names of the users are only shown to logged-in users), `word_groups_count` and `is_favorite` only when loaded; `is_accessible`
     * only in the favorites list, where collections that became private are flagged with `false`; `contains_word_group`
     * only among the collections a group can be added to.
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
            'word_groups_count' => $this->whenCounted('wordGroups'),
            'is_favorite' => $this->whenHas('is_favorite', fn (mixed $isFavorite): bool => (bool) $isFavorite),
            'is_accessible' => $this->whenHas('is_accessible'),
            'access_status' => $this->whenHas('access_status'),
            'contains_word_group' => $this->whenHas('contains_word_group', fn (mixed $containsWordGroup): bool => (bool) $containsWordGroup),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'word_groups' => WordGroupResource::collection($this->whenLoaded('wordGroups')),
            'can' => [
                'update' => Gate::allows('update', $this->resource),
                'delete' => Gate::allows('delete', $this->resource),
                'share' => Gate::allows('share', $this->resource),
                'manage_access' => Gate::allows('manageAccess', $this->resource),
                'leave' => Gate::allows('leave', $this->resource),
                'add_word_group' => Gate::allows('addWordGroup', $this->resource),
                'manage_contributions' => Gate::allows('manageContributions', $this->resource),
                'duplicate' => Gate::allows('duplicate', $this->resource),
            ],
        ];
    }
}
