<?php

namespace App\Http\Requests;

use App\Enums\ShareType;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Changing the type of sharing of a member of a group or collection, for its creator.
 */
class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAccess', $this->item());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'share_type' => ['required', Rule::enum(ShareType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'share_type' => __('fields.share_type'),
        ];
    }

    public function shareType(): ShareType
    {
        return ShareType::from($this->validated('share_type'));
    }

    public function item(): WordGroup|GroupCollection
    {
        return $this->route('word_group') ?? $this->route('group_collection');
    }

    public function member(): User
    {
        return $this->route('user');
    }
}
