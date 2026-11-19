<?php

namespace App\Http\Requests;

use App\Enums\SortDirection;
use App\Enums\WordSort;
use App\Models\WordGroup;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ListWordsRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('view', $this->wordGroup());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sort' => ['nullable', Rule::enum(WordSort::class)],
            'direction' => ['nullable', Rule::enum(SortDirection::class)],
        ];
    }

    /**
     * Most recently added first by default.
     */
    public function sort(): WordSort
    {
        return WordSort::tryFrom((string) $this->validated('sort')) ?? WordSort::Created;
    }

    public function direction(): SortDirection
    {
        return SortDirection::tryFrom((string) $this->validated('direction')) ?? $this->sort()->defaultDirection();
    }

    public function wordGroup(): WordGroup
    {
        return $this->route('word_group');
    }
}
