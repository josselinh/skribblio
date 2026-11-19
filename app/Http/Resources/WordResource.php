<?php

namespace App\Http\Resources;

use App\Enums\WordVote;
use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Word
 */
class WordResource extends JsonResource
{
    /**
     * Transform the resource into an array. Expects `creator` and `wordGroup` to be loaded, and the votes
     * (`withVotes` scope or `loadVotes()`). `creator` is null once the author deleted their account;
     * `my_vote` is the current user's vote, null when they did not vote. `can` tells the SPA which actions
     * the current user may perform.
     *
     * @return array{id: string, name: string, creator: array{id: string, name: string}|null, upvotes_count: int, downvotes_count: int, my_vote: 'up'|'down'|null, created_at: ?string, updated_at: ?string, can: array{update: bool, delete: bool, vote: bool}}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'creator' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'upvotes_count' => (int) $this->upvotes_count,
            'downvotes_count' => (int) $this->downvotes_count,
            'my_vote' => $this->my_vote === null ? null : WordVote::fromScore((int) $this->my_vote)->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'can' => [
                'update' => $request->user()->can('update', $this->resource),
                'delete' => $request->user()->can('delete', $this->resource),
                'vote' => $request->user()->can('vote', $this->resource),
            ],
        ];
    }
}
