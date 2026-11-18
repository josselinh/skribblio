<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Word;
use App\Models\WordGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Word>
 */
class WordFactory extends Factory
{
    /**
     * Define the model's default state. By default, the word is written by the creator of its group.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'word_group_id' => WordGroup::factory(),
            'created_by' => fn (array $attributes) => WordGroup::findOrFail($attributes['word_group_id'])->created_by,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->getKey(),
        ]);
    }
}
