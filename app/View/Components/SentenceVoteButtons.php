<?php

namespace App\View\Components;

use App\Models\Sentence;
use App\Models\Vote;
use Illuminate\View\Component;
use Illuminate\View\View;

class SentenceVoteButtons extends Component
{
    /**
     * @var Sentence
     */
    public $sentence;

    /**
     * @var bool
     */
    public $positiveButton;

    /**
     * @var bool
     */
    public $negativeButton;

    /**
     * Create a new component instance.
     *
     * @param Sentence $sentence
     */
    public function __construct(Sentence $sentence)
    {
        $this->sentence = $sentence;

        $vote = $sentence->votes->filter(function (Vote $vote) {
            return $vote->user_id === auth()->id();
        })->first();

        $this->positiveButton = !$vote || ($vote && $vote->note === -1);
        $this->negativeButton = !$vote || ($vote && $vote->note === +1);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View
     */
    public function render(): View
    {
        return view('components.sentence-vote-buttons');
    }
}
