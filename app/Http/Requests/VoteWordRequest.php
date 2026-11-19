<?php

namespace App\Http\Requests;

use App\Enums\WordVote;
use App\Models\Word;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VoteWordRequest extends FormRequest
{
    /**
     * Users who cannot see the group get a 404, the others need the `vote` ability.
     */
    public function authorize(): Response
    {
        $canView = Gate::inspect('view', $this->word()->wordGroup);

        return $canView->denied() ? $canView : Gate::inspect('vote', $this->word());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vote' => ['required', Rule::enum(WordVote::class)],
        ];
    }

    public function vote(): WordVote
    {
        return WordVote::from($this->validated('vote'));
    }

    public function word(): Word
    {
        return $this->route('word');
    }
}
