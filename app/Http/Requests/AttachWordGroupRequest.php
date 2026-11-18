<?php

namespace App\Http\Requests;

use App\Models\GroupCollection;
use App\Models\WordGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttachWordGroupRequest extends FormRequest
{
    /**
     * Only the creator of the collection can add groups to it.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->groupCollection());
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
     * The group must exist and be attachable by the user (their own or a public one). A private
     * group of another user is reported as not found, without revealing that it exists.
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
                    $validator->errors()->add('word_group_id', 'Ce groupe est introuvable.');
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
