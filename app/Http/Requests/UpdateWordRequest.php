<?php

namespace App\Http\Requests;

use App\Models\Word;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->word());
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
                Rule::unique('words', 'name')
                    ->where('word_group_id', $this->word()->word_group_id)
                    ->ignore($this->word()),
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

    public function word(): Word
    {
        return $this->route('word');
    }
}
