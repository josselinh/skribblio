<?php

namespace App\Http\Requests;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;

class SentenceImportRequest extends FormRequest
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
        return [
            'sentences' => ['required', 'string'],
            'group' => ['required', 'integer', 'exists:App\Models\Group,id'],
        ];
    }
}
