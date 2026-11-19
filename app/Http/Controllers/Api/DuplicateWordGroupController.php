<?php

namespace App\Http\Controllers\Api;

use App\Enums\Visibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\WordGroupResource;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DuplicateWordGroupController extends Controller
{
    /**
     * Suffix added to the name of the copy.
     */
    private const string COPY_SUFFIX = ' (copie)';

    /**
     * Longest group name, as validated on creation.
     */
    private const int NAME_MAX_LENGTH = 255;

    /**
     * Number of words inserted per query.
     */
    private const int INSERT_CHUNK_SIZE = 500;

    /**
     * Copy a group the user can see into a new group of theirs: private, closed to contributions,
     * with every word written by them (so that the copy does not depend on the original authors).
     * Nothing else is copied (favorites, members, share link, collections) and the copy keeps no
     * link to the original.
     */
    public function __invoke(Request $request, WordGroup $wordGroup): JsonResponse
    {
        Gate::authorize('duplicate', $wordGroup);
        $user = $request->user();

        $copy = DB::transaction(function () use ($user, $wordGroup): WordGroup {
            $copy = $user->wordGroups()->create([
                'name' => mb_substr($wordGroup->name, 0, self::NAME_MAX_LENGTH - mb_strlen(self::COPY_SUFFIX)).self::COPY_SUFFIX,
                'visibility' => Visibility::Private,
                'accepts_contributions' => false,
            ]);

            $now = now();
            $wordGroup->words()->select('name')->orderBy('id')->chunk(self::INSERT_CHUNK_SIZE, function ($words) use ($copy, $user, $now): void {
                Word::insert($words->map(fn (Word $word): array => [
                    'id' => $word->newUniqueId(),
                    'name' => $word->name,
                    'word_group_id' => $copy->getKey(),
                    'created_by' => $user->getKey(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });

            return $copy;
        });
        $copy->setAttribute('is_favorite', false);

        return (new WordGroupResource($copy))->response()->setStatusCode(201);
    }
}
