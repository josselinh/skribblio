<?php

namespace App\Http\Requests;

use App\Models\GroupCollection;
use App\Models\WordGroup;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class AttachWordGroupRequest extends FormRequest
{
    /**
     * The creator can add groups, and so can the users who see the collection while it is open to
     * contributions. The policy response is returned as is so that a hidden collection yields a 404.
     */
    public function authorize(): Response
    {
        return Gate::inspect('addWordGroup', $this->groupCollection());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'word_group_id' => ['required', 'uuid'],
        ];
    }

    /**
     * The group must exist, be attachable by the user (any group they can see) and be in the collection's
     * language. A private group of another user is reported as not found, without revealing that it exists.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $wordGroup = $this->wordGroup();
                if ($wordGroup === null || ! $this->user()->can('attachWordGroup', [$this->groupCollection(), $wordGroup])) {
                    $validator->errors()->add('word_group_id', __('word_groups.not_found'));

                    return;
                }
                if ($wordGroup->language !== $this->groupCollection()->language) {
                    $validator->errors()->add('word_group_id', __('group_collections.word_groups.wrong_language', [
                        'group_language' => $wordGroup->language->label(),
                        'language' => $this->groupCollection()->language->label(),
                    ]));
                }
            },
        ];
    }

    public function groupCollection(): GroupCollection
    {
        return $this->route('group_collection');
    }

    public function wordGroup(): ?WordGroup
    {
        return once(fn () => WordGroup::find($this->input('word_group_id')));
    }
}
