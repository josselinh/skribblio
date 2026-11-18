<?php

namespace App\Http\Requests;

use App\Enums\AuthorFilter;
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
     * Any verified user can search; results are limited to what they can see.
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
            'author' => ['nullable', Rule::enum(AuthorFilter::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    public function search(): ?string
    {
        return $this->validated('q');
    }

    public function author(): AuthorFilter
    {
        return AuthorFilter::tryFrom((string) $this->validated('author')) ?? AuthorFilter::All;
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
