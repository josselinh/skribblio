<?php

namespace App\Http\Requests;

use App\Enums\Locale;
use App\Enums\Visibility;
use App\Models\GroupCollection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creates an empty collection: its groups are added afterwards, one by one.
 */
class StoreGroupCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', GroupCollection::class);
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
