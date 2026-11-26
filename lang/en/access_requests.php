<?php

declare(strict_types=1);

return [
    'already_decided' => 'This request has already been handled.',

    'not_shareable' => [
        'word_group' => 'Open the group to contributions to accept this request.',
        'group_collection' => 'Open the collection to contributions to accept this request.',
    ],

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
        'share_type' => [
            'full' => [
                'word_group' => 'Full sharing: while the group is open to contributions, you can edit and delete all its words, including other people\'s.',
                'group_collection' => 'Full sharing: while the collection is open to contributions, you can remove all its groups, including those added by others.',
            ],
            'partial' => [
                'word_group' => 'Partial sharing: you can only edit and delete your own words.',
                'group_collection' => 'Partial sharing: you can only remove the groups you added.',
            ],
        ],
        'share_type_changed' => [
            'subject' => [
                'word_group' => 'Your sharing of the group “:name” has changed',
                'group_collection' => 'Your sharing of the collection “:name” has changed',
            ],
            'line' => [
                'word_group' => ':creator changed your sharing of the group “:name”.',
                'group_collection' => ':creator changed your sharing of the collection “:name”.',
            ],
        ],
    ],
];
