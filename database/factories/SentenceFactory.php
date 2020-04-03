<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Sentence;
use Faker\Generator as Faker;

$factory->define(Sentence::class, function (Faker $faker) {
    return [
        'sentence' => $faker->text(30),
        'note' => $faker->numberBetween(-10, 10),
    ];
});


