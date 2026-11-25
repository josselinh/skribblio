<?php

namespace App\Http\Requests;

use App\Models\WordGroup;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ListCollectionTargetsRequest extends FormRequest
{
    /**
     * Anyone who can see the group can add it to a collection. A private group the user cannot see answers 404.
     */
    public function authorize(): Response
    {
        return Gate::inspect('view', $this->wordGroup());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'group_collection_id' => ['nullable', 'uuid'],
        ];
    }

    public function wordGroup(): WordGroup
    {
        return $this->route('word_group');
    }

    /**
     * The search, trimmed; empty when the user has not typed anything.
     */
    public function search(): string
    {
        return trim((string) $this->validated('q'));
    }
}
