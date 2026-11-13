<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SentenceExportRequest extends FormRequest
{
    /**
     * On failure, go back to the export form without the invalid query string.
     */
    protected $redirectRoute = 'sentence.export';

    /**
     * Export is public: visibility is enforced by SentenceManager.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'groups' => ['sometimes', 'array'],
            'groups.*' => ['integer'],
        ];
    }
}
