<?php

namespace App\Models\Concerns;

use App\Enums\Locale;
use Illuminate\Database\Eloquent\Builder;

/**
 * Language of the content of a group (its words) or of a collection (its groups, which must all be in the
 * collection's language). It is independent of the interface language: a user may browse in English the
 * lists written in French.
 *
 * @property Locale $language
 */
trait HasLanguage
{
    public function initializeHasLanguage(): void
    {
        $this->mergeCasts(['language' => Locale::class]);
        $this->attributes['language'] ??= Locale::En->value;
    }

    /**
     * Keeps the items in this language; every language when null.
     *
     * @param  Builder<static>  $query
     */
    public function scopeInLanguage(Builder $query, ?Locale $language): void
    {
        $query->when($language, fn (Builder $query, Locale $language) => $query->where($this->qualifyColumn('language'), $language->value));
    }
}
