<?php

declare(strict_types=1);

return [
    'name_contains_comma' => 'Un mot ne peut pas contenir de virgule : Skribbl.io s’en sert comme séparateur.',
    'name_too_long' => 'Un mot ne peut pas dépasser :max caractères.',
    'name_taken' => 'Ce mot est déjà dans le groupe.',
    'name_required' => 'Saisissez un mot.',
    'name_repeated' => 'Vous avez saisi ce mot plusieurs fois.',
    'no_longer_in_group' => 'Ce mot n’est plus dans le groupe.',
    'add' => [
        'required' => 'Saisissez au moins un mot.',
        'max' => 'Vous pouvez ajouter au plus :max mots à la fois.',
    ],
    'update' => [
        'required' => 'Modifiez au moins un mot.',
        'max' => 'Vous pouvez modifier au plus :max mots à la fois.',
    ],
    'delete' => [
        'required' => 'Sélectionnez au moins un mot.',
        'max' => 'Vous pouvez supprimer au plus :max mots à la fois.',
        'no_longer_in_group' => 'Un des mots sélectionnés n’est plus dans le groupe.',
    ],
];
