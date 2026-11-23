<?php

namespace App\Http\Requests;

use App\Enums\AuthorFilter;
use App\Enums\Locale;
use App\Enums\SearchSort;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
{
    /**
     * Default and maximum number of results per page.
     */
    public const int DEFAULT_PER_PAGE = 12;

    public const int MAX_PER_PAGE = 24;

    /**
     * Sorts that only logged-in users can use.
     */
    public const array MEMBERS_ONLY_SORTS = [SearchSort::Popular, SearchSort::Largest];

    /**
     * Anyone can search, guests included; results are limited to what they can see (public items for guests).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', Rule::enum(Locale::class)],
            'author' => ['nullable', $this->user() ? Rule::enum(AuthorFilter::class) : Rule::enum(AuthorFilter::class)->only(AuthorFilter::All)],
            'sort' => ['nullable', $this->user() ? Rule::enum(SearchSort::class) : Rule::enum(SearchSort::class)->except(self::MEMBERS_ONLY_SORTS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    /**
     * Guests can neither filter by author (they have created nothing) nor use the members-only sorts (popularity, size).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->user() ? [] : [
            'author.enum' => __('search.login_required.author'),
            'sort.enum' => __('search.login_required.sort'),
        ];
    }

    public function search(): ?string
    {
        return $this->validated('q');
    }

    /**
     * Language of the content searched; every language when none is given.
     */
    public function language(): ?Locale
    {
        return Locale::tryFrom((string) $this->validated('language'));
    }

    public function author(): AuthorFilter
    {
        return AuthorFilter::tryFrom((string) $this->validated('author')) ?? AuthorFilter::All;
    }

    public function sort(): SearchSort
    {
        return SearchSort::tryFrom((string) $this->validated('sort')) ?? SearchSort::Recent;
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
