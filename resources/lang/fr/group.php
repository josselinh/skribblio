<?php

return [
    'index' => [
        'search' => [
            'submit' => 'Rechercher',
        ],
        'table' => [
            'th' => [
                'name' => 'Nom',
                'sentences' => 'Phrases',
                'author' => 'Auteur',
                'created_at' => 'Créée le',
            ]
        ],
    ],
    'add' => [
        'name' => [
            'label' => 'Nom du groupe',
        ],
        'visibility' => [
            'label' => 'Visibilité',
        ],
        'submit' => 'Ajouter',
    ],
    'doAdd' => [
        'success' => 'Le groupe a été ajouté avec succès',
    ],
    'visibility' => [
        'public' => 'Public',
        'public_unalterable' => 'Public non-modifiable',
        'private' => 'Privée',
    ]
];
