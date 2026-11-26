<?php

namespace App\Http\Requests;

use App\Enums\ShareType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Accepting an access request, with the type of sharing granted to the requester (partial by default).
 * Authorized by the controller, which also handles refusals.
 */
class AcceptAccessRequestRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'share_type' => ['sometimes', Rule::enum(ShareType::class)],
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
        return ShareType::tryFrom((string) $this->validated('share_type')) ?? ShareType::Partial;
    }
}
