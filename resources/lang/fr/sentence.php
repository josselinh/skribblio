<?php

return [
    'index' => [
        'search' => [
            'submit' => 'Rechercher',
        ],
        'table' => [
            'th' => [
                'sentence' => 'Phrase',
                'note' => 'Note',
                'group' => 'Groupe',
                'author' => 'Auteur',
                'created_at' => 'Créée le',
            ],
        ],
        'add' => [
            'name' => [
                'label' => 'Phrase',
            ],
            'group' => [
                'label' => 'Groupe',
                'placeholder' => 'Sélectionner un groupe',
            ],
            'submit' => 'Ajouter',
        ],
    ],
    'doAdd' => [
        'success' => 'La phrase a été ajouté avec succès',
    ],
    'import' => [
        'label' => 'Liste de phrases à importer (séparées par une virgule)',
        'group' => [
            'label' => 'Groupe',
            'placeholder' => 'Sélectionner un groupe',
        ],
        'submit' => 'Importer',
    ],
    'doImport' => [
        'success' => 'L\'import a été effectué avec succès',
    ],
    'export' => [
        'groups' => [
            'title' => 'Groupes',
        ],
        'users' => [
            'title' => 'Auteurs',
        ],
        'submit' => 'Exporter',
    ],
];
