<?php

namespace App\Http\Requests;

use App\Enums\Locale;
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
            'language' => ['sometimes', Rule::enum(Locale::class)],
            'visibility' => ['required', Rule::enum(Visibility::class)],
            'accepts_contributions' => ['sometimes', 'boolean'],
            'contributions_members_only' => ['sometimes', 'boolean'],
            'word_group_ids' => ['required', 'array', 'min:1'],
            'word_group_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }

    /**
     * Every selected group must exist, be visible to the user (their own groups or public ones) and be in the
     * collection's language.
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
                    $validator->errors()->add('word_group_ids', __('group_collections.word_groups.not_found'));

                    return;
                }
                $language = $this->language();
                if ($this->wordGroups()->contains(fn (WordGroup $wordGroup): bool => $wordGroup->language !== $language)) {
                    $validator->errors()->add('word_group_ids', __('group_collections.word_groups.language_mismatch', ['language' => $language->label()]));
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
            'word_group_ids' => __('fields.word_group_ids'),
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
            'word_group_ids.required' => __('group_collections.word_groups.required'),
            'word_group_ids.min' => __('group_collections.word_groups.required'),
        ];
    }

    /**
     * Language of the collection: the one given, otherwise the one of its first group.
     */
    public function language(): Locale
    {
        return Locale::tryFrom((string) $this->validated('language')) ?? $this->wordGroups()->first()->language;
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
