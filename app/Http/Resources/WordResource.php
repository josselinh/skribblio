<?php

namespace App\Http\Resources;

use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Word
 */
class WordResource extends JsonResource
{
    /**
     * Transform the resource into an array. Expects `creator` and `wordGroup` to be loaded.
     * `can` tells the SPA which actions the current user may perform.
     *
     * @return array{id: string, name: string, creator: array{id: string, name: string}, created_at: ?string, updated_at: ?string, can: array{update: bool, delete: bool}}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'creator' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'can' => [
                'update' => $request->user()->can('update', $this->resource),
                'delete' => $request->user()->can('delete', $this->resource),
            ],
        ];
    }
}
