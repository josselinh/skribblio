<?php

namespace App\Http\Controllers\Api;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Http\Resources\WordGroupResource;
use App\Models\WordGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SearchWordGroupController extends Controller
{
    /**
     * Maximum number of groups returned.
     */
    private const int LIMIT = 10;

    /**
     * Search the groups visible to the current user (their own groups and public ones) by name.
     * The user's own groups come first. `language` keeps the groups in that language (e.g. those that can be
     * added to a collection).
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', Rule::enum(Locale::class)],
        ]);
        $user = $request->user();

        $wordGroups = WordGroup::visibleTo($user)
            ->with('creator')
            ->nameContains($request->input('q'))
            ->inLanguage($request->enum('language', Locale::class))
            ->orderByRaw('created_by = ? desc', [$user->getKey()])
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();

        return WordGroupResource::collection($wordGroups);
    }
}
