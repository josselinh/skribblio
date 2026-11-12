<?php

namespace App\Http\Requests;

use App\Models\Group;
use App\Models\Sentence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SentenceAddRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $groupId = $this->input('group');
        if (!$groupId) {
            return true;
        }

        $group = Group::find($groupId);
        if (!$group) {
            return true;
        }

        return $this->user()->can('addSentence', $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => ['required', 'string', 'max:30'],
            'group' => ['required', 'integer', 'exists:App\Models\Group,id']
        ];

        if ($this->has('group')) {
            $rules['name'][] = Rule::unique(Sentence::class, 'sentence')->where('group_id', $this->input('group'));
        }

        return $rules;
    }
}
