<?php

namespace App\Http\Requests;

use App\Enums\Visibility;
use App\Models\GroupCollection;
use App\Models\WordGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
     * Get the validation rules that apply to the request. A collection must contain at least one group.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'visibility' => ['required', Rule::enum(Visibility::class)],
            'word_group_ids' => ['required', 'array', 'min:1'],
            'word_group_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }

    /**
     * Every selected group must exist and be visible to the user (their own groups or public ones).
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
                if ($this->wordGroups()->count() !== count($this->input('word_group_ids'))) {
                    $validator->errors()->add('word_group_ids', 'Un ou plusieurs groupes sélectionnés sont introuvables.');
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
            'visibility' => 'visibilité',
            'word_group_ids' => 'groupes',
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
            'word_group_ids.required' => 'Choisissez au moins un groupe.',
            'word_group_ids.min' => 'Choisissez au moins un groupe.',
        ];
    }

    /**
     * The selected groups the user is allowed to attach.
     *
     * @return Collection<int, WordGroup>
     */
    public function wordGroups(): Collection
    {
        return once(fn () => WordGroup::visibleTo($this->user())
            ->whereKey($this->input('word_group_ids', []))
            ->get());
    }
}
