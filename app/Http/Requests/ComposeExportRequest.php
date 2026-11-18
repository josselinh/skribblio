<?php

namespace App\Http\Requests;

use App\Models\GroupCollection;
use App\Models\WordGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class ComposeExportRequest extends FormRequest
{
    /**
     * Most groups, and most collections, that one export can combine.
     */
    public const int MAX_ITEMS = 50;

    /**
     * Any verified user may compose an export; each selected item is checked in `after()`.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request. At least one group or collection is required.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'word_group_ids' => ['required_without:group_collection_ids', 'array', 'max:'.self::MAX_ITEMS],
            'word_group_ids.*' => ['required', 'uuid', 'distinct'],
            'group_collection_ids' => ['required_without:word_group_ids', 'array', 'max:'.self::MAX_ITEMS],
            'group_collection_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }

    /**
     * Every selected group and collection must exist and be visible to the user.
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
                $visibleWordGroupsCount = WordGroup::visibleTo($this->user())->whereKey($this->wordGroupIds())->count();
                $visibleGroupCollectionsCount = GroupCollection::visibleTo($this->user())->whereKey($this->groupCollectionIds())->count();
                if ($visibleWordGroupsCount !== $this->wordGroupIds()->count()
                    || $visibleGroupCollectionsCount !== $this->groupCollectionIds()->count()) {
                    $validator->errors()->add('items', 'Un ou plusieurs éléments sélectionnés sont introuvables.');
                }
            },
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
            'word_group_ids.required_without' => 'Choisissez au moins un groupe ou une collection.',
            'group_collection_ids.required_without' => 'Choisissez au moins un groupe ou une collection.',
        ];
    }

    /**
     * @return Collection<int, string>
     */
    public function wordGroupIds(): Collection
    {
        return collect($this->input('word_group_ids', []));
    }

    /**
     * @return Collection<int, string>
     */
    public function groupCollectionIds(): Collection
    {
        return collect($this->input('group_collection_ids', []));
    }
}
