<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WordGroupResource;
use App\Models\WordGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchWordGroupController extends Controller
{
    /**
     * Maximum number of groups returned.
     */
    private const int LIMIT = 10;

    /**
     * Search the groups visible to the current user (their own groups and public ones) by name.
     * The user's own groups come first.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $user = $request->user();
        $search = addcslashes($request->string('q')->trim()->toString(), '\\%_');

        $wordGroups = WordGroup::visibleTo($user)
            ->with('creator')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderByRaw('created_by = ? desc', [$user->getKey()])
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();

        return WordGroupResource::collection($wordGroups);
    }
}
