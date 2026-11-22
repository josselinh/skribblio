<?php

namespace App\Http\Requests;

use App\Models\GroupCollection;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Removes several groups from a collection at once. Every group must be in the collection, and the
 * collection must keep at least one group (hidden ones included); the controller checks that the user
 * may remove each of them.
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
     * The collection must keep at least one group.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty()
                    && count($this->wordGroupIds()) >= $this->groupCollection()->wordGroups()->count()) {
                    $validator->errors()->add('word_group_ids', 'Une collection doit contenir au moins un groupe.');
                }
            },
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
            'word_group_ids.required' => 'Sélectionnez au moins un groupe.',
            'word_group_ids.max' => 'Vous pouvez retirer au plus '.self::MAX_WORD_GROUPS.' groupes à la fois.',
            'word_group_ids.*.exists' => 'Un des groupes sélectionnés n’est plus dans la collection.',
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
