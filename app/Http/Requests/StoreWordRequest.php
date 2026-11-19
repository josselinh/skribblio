<?php

namespace App\Http\Requests;

use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Adds several words at once. The request is lenient: invalid words are reported by
 * `invalidNames()` instead of failing the whole request, so that valid ones are still added.
 */
class StoreWordRequest extends FormRequest
{
    /**
     * Most words accepted in a single request.
     */
    public const int MAX_NAMES = 500;

    /**
     * Determine if the user is authorized to make this request. The policy response is
     * returned as is so that a private group of another user yields a 404, not a 403.
     */
    public function authorize(): Response
    {
        return Gate::inspect('addWord', $this->wordGroup());
    }

    /**
     * Get the validation rules that apply to the request. Each name is trimmed by the
     * middleware, and empty ones become null and are ignored.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'names' => ['required', 'array', 'max:'.self::MAX_NAMES],
            'names.*' => ['nullable', 'string'],
        ];
    }

    /**
     * At least one name must remain once empty ones are ignored.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && $this->submittedNames() === []) {
                    $validator->errors()->add('names', 'Saisissez au moins un mot.');
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
            'names.required' => 'Saisissez au moins un mot.',
            'names.max' => 'Vous pouvez ajouter au plus '.self::MAX_NAMES.' mots à la fois.',
        ];
    }

    /**
     * Valid names, in the order they were submitted.
     *
     * @return list<string>
     */
    public function validNames(): array
    {
        return array_values(array_filter($this->submittedNames(), fn (string $name): bool => $this->errorFor($name) === null));
    }

    /**
     * Names that cannot be words, with the reason.
     *
     * @return list<array{name: string, message: string}>
     */
    public function invalidNames(): array
    {
        $invalidNames = [];
        foreach ($this->submittedNames() as $name) {
            $message = $this->errorFor($name);
            if ($message !== null) {
                $invalidNames[] = ['name' => $name, 'message' => $message];
            }
        }

        return $invalidNames;
    }

    public function wordGroup(): WordGroup
    {
        return $this->route('word_group');
    }

    /**
     * Non-empty submitted names, without exact repetitions.
     *
     * @return list<string>
     */
    private function submittedNames(): array
    {
        $names = array_filter((array) $this->input('names', []), fn (mixed $name): bool => is_string($name) && $name !== '');

        return array_values(array_unique($names));
    }

    private function errorFor(string $name): ?string
    {
        return match (true) {
            str_contains($name, ',') => 'Un mot ne peut pas contenir de virgule : Skribbl.io s’en sert comme séparateur.',
            mb_strlen($name) > Word::NAME_MAX_LENGTH => 'Un mot ne peut pas dépasser '.Word::NAME_MAX_LENGTH.' caractères.',
            default => null,
        };
    }
}
