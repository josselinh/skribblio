<?php

namespace App\Actions\Fortify;

use App\Enums\InterfaceLocale;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user, in the language they were using (so that the verification
     * email is sent in it), which is also, until they change it, the language of the lists they browse when the
     * content is offered in it (English otherwise).
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', Rule::unique(User::class)],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ], [], ['name' => __('fields.pseudo')])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'locale' => $locale = InterfaceLocale::tryFrom(App::currentLocale()) ?? InterfaceLocale::En,
            'content_locale' => $locale->contentLocale(),
        ]);
    }
}
