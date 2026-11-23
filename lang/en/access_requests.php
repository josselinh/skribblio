<?php

declare(strict_types=1);

return [
    'already_decided' => 'This request has already been handled.',

    /*
     * Names the requested item after "access" or "contribute".
     */
    'target' => [
        'word_group' => 'to the group “:name”',
        'group_collection' => 'to the collection “:name”',
        'hidden_word_group' => 'to a private group',
    ],

    'mail' => [
        'greeting' => 'Hello :name,',
        'open' => 'Open',
        'requested' => [
            'access' => ':requester is asking for access :target',
            'contribution' => ':requester is asking to contribute :target',
            'action' => 'See the request',
            'outro' => 'You can accept or refuse the request from this page or from your dashboard.',
        ],
        'access_accepted' => [
            'subject' => 'Access granted :target',
            'line' => ':creator gave you access :target.',
        ],
        'access_refused' => [
            'subject' => 'Access request refused',
            'line' => ':creator refused your request for access :target.',
        ],
        'contribution_accepted' => [
            'subject' => 'You can contribute :target',
            'line' => ':creator allows you to contribute :target.',
        ],
        'contribution_refused' => [
            'subject' => 'Contribution request refused',
            'line' => ':creator refused your request to contribute :target.',
        ],
    ],
];
