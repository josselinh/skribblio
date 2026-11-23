<?php

namespace App\Http\Requests;

use App\Enums\Visibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWordGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('word_group'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'language' => ['prohibited'],
            'visibility' => ['required', Rule::enum(Visibility::class)],
            'accepts_contributions' => ['sometimes', 'boolean'],
            'contributions_members_only' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The language is chosen on creation and never changes (a collection never mixes languages).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'language.prohibited' => __('word_groups.language_locked'),
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
            'visibility' => __('fields.visibility'),
            'accepts_contributions' => __('fields.accepts_contributions'),
            'contributions_members_only' => __('fields.contributions_members_only'),
        ];
    }
}
