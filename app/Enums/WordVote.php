<?php

namespace App\Enums;

/**
 * A user's opinion of a word in a group open to contributions, stored in `word_votes.value`
 * as +1 or -1 so that the score of a word is the sum of its votes.
 */
enum WordVote: string
{
    case Up = 'up';

    case Down = 'down';

    public static function fromScore(int $score): self
    {
        return $score > 0 ? self::Up : self::Down;
    }

    public function score(): int
    {
        return match ($this) {
            self::Up => 1,
            self::Down => -1,
        };
    }
}
