<?php

namespace App\Http\Requests;

use App\Models\GroupCollection;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Removes several groups from a collection at once, possibly all of them. Every group must be in the
 * collection; the controller checks that the user may remove each of them.
 */
class DetachWordGroupsRequest extends FormRequest
{
    /**
     * Most groups removed in a single request.
     */
    public const int MAX_WORD_GROUPS = 100;

    /**
     * Users who cannot see the collection get a 404, not a 403.
     */
    public function authorize(): Response
    {
        return Gate::inspect('view', $this->groupCollection());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'word_group_ids' => ['required', 'array', 'max:'.self::MAX_WORD_GROUPS],
            'word_group_ids.*' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('groups_collections', 'word_group_id')
                    ->where('group_collection_id', $this->groupCollection()->getKey()),
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
            'word_group_ids.required' => __('group_collections.remove_word_groups.required'),
            'word_group_ids.max' => __('group_collections.remove_word_groups.max', ['max' => self::MAX_WORD_GROUPS]),
            'word_group_ids.*.exists' => __('group_collections.remove_word_groups.no_longer_in_collection'),
        ];
    }

    /**
     * @return list<string>
     */
    public function wordGroupIds(): array
    {
        return array_values((array) $this->input('word_group_ids', []));
    }

    public function groupCollection(): GroupCollection
    {
        return $this->route('group_collection');
    }
}
