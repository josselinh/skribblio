<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GroupAddRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:30'],
            'visibility' => ['required', 'int', 'in:1,2,3'],
        ];
    }
}
