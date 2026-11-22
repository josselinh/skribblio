<?php

namespace App\Http\Requests;

use App\Models\WordGroup;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Deletes several words of a group at once. Every word must belong to the group; the controller
 * checks that the user may delete each of them.
 */
class DestroyWordsRequest extends FormRequest
{
    /**
     * Most words deleted in a single request.
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'word_ids' => ['required', 'array', 'max:'.self::MAX_WORDS],
            'word_ids.*' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('words', 'id')->where('word_group_id', $this->wordGroup()->getKey()),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'word_ids.required' => __('words.delete.required'),
            'word_ids.max' => __('words.delete.max', ['max' => self::MAX_WORDS]),
            'word_ids.*.exists' => __('words.delete.no_longer_in_group'),
        ];
    }

    /**
     * @return list<string>
     */
    public function wordIds(): array
    {
        return $this->validated('word_ids');
    }

    public function wordGroup(): WordGroup
    {
        return $this->route('word_group');
    }
}
