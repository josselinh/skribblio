<?php

namespace App\Http\Requests;

use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreWordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The policy response is
     * returned as is so that a private group of another user yields a 404, not a 403.
     */
    public function authorize(): Response
    {
        return Gate::inspect('addWord', $this->wordGroup());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:'.Word::NAME_MAX_LENGTH,
                'not_regex:/,/',
                Rule::unique('words', 'name')->where('word_group_id', $this->wordGroup()->getKey()),
            ],
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
            'name.unique' => 'Ce mot est déjà dans le groupe.',
            'name.not_regex' => 'Un mot ne peut pas contenir de virgule : Skribbl.io s’en sert comme séparateur.',
        ];
    }

    public function wordGroup(): WordGroup
    {
        return $this->route('word_group');
    }
}
