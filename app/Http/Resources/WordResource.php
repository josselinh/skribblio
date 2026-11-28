<?php

namespace App\Http\Resources;

use App\Enums\WordVote;
use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin Word
 */
class WordResource extends JsonResource
{
    /**
     * Transform the resource into an array. Expects `creator` and `wordGroup` to be loaded, and the votes
     * (`withVotes` scope or `loadVotes()`). `creator` is null once the author deleted their account, and left out for guests (the names of the users are only shown to logged-in users);
     * `my_vote` is the current user's vote, null when they did not vote. `can` tells the SPA which actions
     * the current user may perform.
     *
     * @return array{id: string, name: string, creator?: array{id: string, name: string}|null, upvotes_count: int, downvotes_count: int, my_vote: 'up'|'down'|null, created_at: ?string, updated_at: ?string, can: array{update: bool, delete: bool, vote: bool}}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'creator' => $this->when($request->user() !== null, fn (): ?array => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'updater' => $this->when($request->user() !== null && $this->relationLoaded('updater'), fn (): ?array => $this->updater ? [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
            ] : null),
            'upvotes_count' => (int) $this->upvotes_count,
            'downvotes_count' => (int) $this->downvotes_count,
            'my_vote' => $this->my_vote === null ? null : WordVote::fromScore((int) $this->my_vote)->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'can' => [
                'update' => Gate::allows('update', $this->resource),
                'delete' => Gate::allows('delete', $this->resource),
                'vote' => Gate::allows('vote', $this->resource),
            ],
        ];
    }
}
