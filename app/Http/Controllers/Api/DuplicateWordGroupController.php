<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WordGroupResource;
use App\Models\WordGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DuplicateWordGroupController extends Controller
{
    /**
     * Copy a group the user can see into a new group of theirs (see `WordGroup::duplicateFor()`).
     */
    public function __invoke(Request $request, WordGroup $wordGroup): JsonResponse
    {
        Gate::authorize('duplicate', $wordGroup);

        $copy = $wordGroup->duplicateFor($request->user());
        $copy->setAttribute('is_favorite', false);

        return (new WordGroupResource($copy))->response()->setStatusCode(201);
    }
}
