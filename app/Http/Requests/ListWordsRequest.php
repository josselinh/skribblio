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
    /**
     * Sorts that guests cannot use: they do not see the authors of the words, and sorting by votes is a
     * members' feature.
     */
    public const array MEMBERS_ONLY_SORTS = [WordSort::Author, WordSort::Score];

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
            'sort' => ['nullable', $this->user() ? Rule::enum(WordSort::class) : Rule::enum(WordSort::class)->except(self::MEMBERS_ONLY_SORTS)],
            'direction' => ['nullable', Rule::enum(SortDirection::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->user() ? [] : ['sort.enum' => __('search.login_required.word_sort')];
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
