<?php

declare(strict_types=1);

return [
    'name_contains_comma' => 'A word cannot contain a comma: Skribbl.io uses it as a separator.',
    'name_too_long' => 'A word cannot be longer than :max characters.',
    'name_taken' => 'This word is already in the group.',
    'name_required' => 'Enter a word.',
    'name_repeated' => 'You entered this word several times.',
    'no_longer_in_group' => 'This word is no longer in the group.',
    'add' => [
        'required' => 'Enter at least one word.',
        'max' => 'You can add at most :max words at once.',
    ],
    'update' => [
        'required' => 'Change at least one word.',
        'max' => 'You can change at most :max words at once.',
    ],
    'delete' => [
        'required' => 'Select at least one word.',
        'max' => 'You can delete at most :max words at once.',
        'no_longer_in_group' => 'One of the selected words is no longer in the group.',
    ],
];
