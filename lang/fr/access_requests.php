<?php

declare(strict_types=1);

return [
    'already_decided' => 'Cette demande a déjà été traitée.',

    'not_shareable' => [
        'word_group' => 'Ouvrez le groupe aux contributions pour accepter cette demande.',
        'group_collection' => 'Ouvrez la collection aux contributions pour accepter cette demande.',
    ],

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
        'share_type' => [
            'full' => [
                'word_group' => 'Partage total : tant que le groupe est ouvert aux contributions, vous pouvez modifier et supprimer tous ses mots, y compris ceux des autres.',
                'group_collection' => 'Partage total : tant que la collection est ouverte aux contributions, vous pouvez retirer tous ses groupes, y compris ceux ajoutés par d\'autres.',
            ],
            'partial' => [
                'word_group' => 'Partage partiel : vous ne pouvez modifier et supprimer que vos propres mots.',
                'group_collection' => 'Partage partiel : vous ne pouvez retirer que les groupes que vous avez ajoutés.',
            ],
        ],
        'share_type_changed' => [
            'subject' => [
                'word_group' => 'Votre partage du groupe « :name » a changé',
                'group_collection' => 'Votre partage de la collection « :name » a changé',
            ],
            'line' => [
                'word_group' => ':creator a modifié votre partage du groupe « :name ».',
                'group_collection' => ':creator a modifié votre partage de la collection « :name ».',
            ],
        ],
    ],
];
