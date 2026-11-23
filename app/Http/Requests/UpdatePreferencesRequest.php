<?php

namespace App\Http\Requests;

use App\Enums\EmailNotification;
use App\Enums\Locale;
use App\Enums\Theme;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The current user's preferences: the theme, the interface language, the language of the lists they browse and the emails they receive. Each field is optional, and only the
 * kinds of emails given are changed.
 */
class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'theme' => ['sometimes', Rule::enum(Theme::class)],
            'locale' => ['sometimes', Rule::enum(Locale::class)],
            'content_locale' => ['sometimes', Rule::enum(Locale::class)],
            'email_notifications' => ['sometimes', 'array:'.implode(',', EmailNotification::values())],
            'email_notifications.*' => ['boolean'],
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
            'theme' => __('fields.theme'),
            'locale' => __('fields.locale'),
            'content_locale' => __('fields.content_locale'),
            'email_notifications' => __('fields.email_notifications'),
            'email_notifications.*' => __('fields.email_notifications'),
        ];
    }
}
