<?php

declare(strict_types=1);

return [
    'already_decided' => 'Cette demande a déjà été traitée.',

    /*
     * Names the requested item after "accès" or "contribuer".
     */
    'target' => [
        'word_group' => 'au groupe « :name »',
        'group_collection' => 'à la collection « :name »',
        'hidden_word_group' => 'à un groupe privé',
    ],

    'mail' => [
        'greeting' => 'Bonjour :name,',
        'open' => 'Ouvrir',
        'requested' => [
            'access' => ':requester demande l\'accès :target',
            'contribution' => ':requester demande à contribuer :target',
            'action' => 'Voir la demande',
            'outro' => 'Vous pouvez accepter ou refuser la demande depuis cette page ou depuis votre tableau de bord.',
        ],
        'access_accepted' => [
            'subject' => 'Accès accordé :target',
            'line' => ':creator vous a donné accès :target.',
        ],
        'access_refused' => [
            'subject' => 'Demande d\'accès refusée',
            'line' => ':creator a refusé votre demande d\'accès :target.',
        ],
        'contribution_accepted' => [
            'subject' => 'Vous pouvez contribuer :target',
            'line' => ':creator vous autorise à contribuer :target.',
        ],
        'contribution_refused' => [
            'subject' => 'Demande de contribution refusée',
            'line' => ':creator a refusé votre demande de contribution :target.',
        ],
    ],
];
