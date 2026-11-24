<?php

namespace App\Http\Requests;

use App\Models\GroupCollection;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DuplicateGroupCollectionRequest extends FormRequest
{
    /**
     * Users who cannot see the collection get a 404.
     */
    public function authorize(): Response
    {
        return Gate::inspect('duplicate', $this->groupCollection());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'copy_word_groups' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'copy_word_groups' => __('fields.copy_word_groups'),
        ];
    }

    /**
     * Whether the groups are copied too (the default) or the copy contains the original groups.
     */
    public function copiesWordGroups(): bool
    {
        return $this->boolean('copy_word_groups', true);
    }

    public function groupCollection(): GroupCollection
    {
        return $this->route('group_collection');
    }
}
