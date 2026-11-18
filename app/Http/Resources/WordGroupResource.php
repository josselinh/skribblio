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
     * Transform the resource into an array. `can` tells the SPA which actions the current user may perform.
     *
     * @return array{id: string, name: string, visibility: string, created_at: ?string, updated_at: ?string, can: array{update: bool, add_word: bool}}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'visibility' => $this->visibility->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'can' => [
                'update' => $request->user()->can('update', $this->resource),
                'add_word' => $request->user()->can('addWord', $this->resource),
            ],
        ];
    }
}
