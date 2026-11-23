<?php

namespace App\Http\Requests;

use App\Enums\Locale;
use App\Enums\Visibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateGroupCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group_collection'));
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
            'language' => ['sometimes', Rule::enum(Locale::class)],
            'visibility' => ['required', Rule::enum(Visibility::class)],
            'accepts_contributions' => ['sometimes', 'boolean'],
            'contributions_members_only' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * A collection never mixes languages: it only changes language when every group it contains, hidden ones
     * included, is already in the new one.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $language = Locale::tryFrom((string) $this->input('language'));
                if ($validator->errors()->isNotEmpty() || $language === null) {
                    return;
                }
                $groupCollection = $this->route('group_collection');
                if ($groupCollection->wordGroups()->where('word_groups.language', '!=', $language->value)->exists()) {
                    $validator->errors()->add('language', __('group_collections.language.groups_mismatch', ['language' => $language->label()]));
                }
            },
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
            'language' => __('fields.language'),
            'visibility' => __('fields.visibility'),
            'accepts_contributions' => __('fields.accepts_contributions'),
            'contributions_members_only' => __('fields.contributions_members_only'),
        ];
    }
}
