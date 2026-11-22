<?php

namespace App\Http\Requests;

use App\Models\Word;
use App\Models\WordGroup;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Renames several words of a group at once. Every word must belong to the group and every new name
 * must be free in the group: a name held by another word, even one renamed in the same request, is
 * refused. The controller checks that the user may update each word.
 */
class UpdateWordsRequest extends FormRequest
{
    /**
     * Most words renamed in a single request.
     */
    public const int MAX_WORDS = 100;

    /**
     * Users who cannot see the group get a 404, not a 403.
     */
    public function authorize(): Response
    {
        return Gate::inspect('view', $this->wordGroup());
    }

    /**
     * Get the validation rules that apply to the request. Each new name must be free in the group,
     * ignoring the word it renames.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'words' => ['required', 'array', 'max:'.self::MAX_WORDS],
            'words.*' => ['required', 'array:id,name'],
            'words.*.id' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('words', 'id')->where('word_group_id', $this->wordGroup()->getKey()),
            ],
            'words.*.name' => [
                'required',
                'string',
                'max:'.Word::NAME_MAX_LENGTH,
                'not_regex:/,/',
                'distinct:ignore_case',
                $this->isFreeInTheGroup(...),
            ],
        ];
    }

    /**
     * Fails when another word of the group already has the name (as the database compares names),
     * the word renamed by this entry excepted.
     */
    private function isFreeInTheGroup(string $attribute, mixed $value, Closure $fail): void
    {
        $wordId = $this->input(Str::replaceLast('.name', '.id', $attribute));

        $isTaken = $this->wordGroup()->words()
            ->where('name', $value)
            ->when(is_string($wordId) && Str::isUuid($wordId), fn (Builder $query) => $query->whereKeyNot($wordId))
            ->exists();

        if ($isTaken) {
            $fail(__('words.name_taken'));
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'words.required' => __('words.update.required'),
            'words.max' => __('words.update.max', ['max' => self::MAX_WORDS]),
            'words.*.id.exists' => __('words.no_longer_in_group'),
            'words.*.name.required' => __('words.name_required'),
            'words.*.name.max' => __('words.name_too_long', ['max' => Word::NAME_MAX_LENGTH]),
            'words.*.name.not_regex' => __('words.name_contains_comma'),
            'words.*.name.distinct' => __('words.name_repeated'),
        ];
    }

    /**
     * New names, keyed by word id.
     *
     * @return array<string, string>
     */
    public function namesByWordId(): array
    {
        return collect($this->validated('words'))->pluck('name', 'id')->all();
    }

    public function wordGroup(): WordGroup
    {
        return $this->route('word_group');
    }
}
