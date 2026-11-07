<?php

namespace Database\Factories;

use App\Models\Sentence;
use Illuminate\Database\Eloquent\Factories\Factory;

class SentenceFactory extends Factory
{
    protected $model = Sentence::class;

    public function definition(): array
    {
        return [
            'sentence' => $this->faker->text(30),
            'note' => $this->faker->numberBetween(-10, 10),
        ];
    }
}
