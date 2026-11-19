<?php

namespace Database\Factories;

use App\Enums\Visibility;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WordGroup>
 */
class WordGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'visibility' => Visibility::Private,
            'created_by' => User::factory(),
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => Visibility::Public,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => Visibility::Private,
        ]);
    }

    public function openToContributions(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepts_contributions' => true,
        ]);
    }

    public function closedToContributions(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepts_contributions' => false,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->getKey(),
        ]);
    }
}
