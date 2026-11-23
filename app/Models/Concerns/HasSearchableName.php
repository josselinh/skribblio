<?php

namespace App\Models\Concerns;

use App\Enums\AuthorFilter;
use App\Enums\SearchSort;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Name search and author filtering for models with a `name` and a `created_by` column.
 */
trait HasSearchableName
{
    /**
     * Keeps the records whose name contains the search (case-insensitive, LIKE wildcards are literal).
     * An empty search keeps everything.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function nameContains(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search !== '') {
            $query->where($this->qualifyColumn('name'), 'like', '%'.self::escapeLike($search).'%');
        }
    }

    /**
     * Puts the names starting with the search first.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function orderByNameRelevance(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search !== '') {
            $query->orderByRaw($this->qualifyColumn('name').' like ? desc', [self::escapeLike($search).'%']);
        }
    }

    /**
     * Orders by the chosen sort, then by most recently updated.
     * Sorting by popularity requires a `favoritedBy()` relation; sorting by size requires the given count
     * column to be loaded (e.g. `withCount('words')` for `words_count`).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function orderBySearchSort(Builder $query, SearchSort $sort, string $sizeCountColumn): void
    {
        match ($sort) {
            SearchSort::Recent => null,
            SearchSort::Popular => $query->withCount('favoritedBy')->orderByDesc('favorited_by_count'),
            SearchSort::Largest => $query->orderByDesc($sizeCountColumn),
            SearchSort::Name => $query->orderBy($this->qualifyColumn('name')),
        };

        $query->latest($this->qualifyColumn('updated_at'))->latest($this->qualifyColumn('id'));
    }

    /**
     * Keeps the records created by the user, by other users, or both. A guest (null) has created nothing:
     * "me" keeps nothing and "others" everything.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function createdByAuthor(Builder $query, AuthorFilter $author, ?User $user): void
    {
        match (true) {
            $author === AuthorFilter::All, $user === null && $author === AuthorFilter::Others => null,
            $user === null => $query->whereRaw('1 = 0'),
            $author === AuthorFilter::Me => $query->where($this->qualifyColumn('created_by'), $user->getKey()),
            $author === AuthorFilter::Others => $query->where($this->qualifyColumn('created_by'), '!=', $user->getKey()),
        };
    }

    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
