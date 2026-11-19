<?php

namespace Database\Factories;

use App\Enums\Visibility;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupCollection>
 */
class GroupCollectionFactory extends Factory
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

    /**
     * A collection must contain at least one group: when none was attached
     * (e.g. with hasAttached()), one group of the same creator is added.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (GroupCollection $groupCollection): void {
            if (! $groupCollection->wordGroups()->exists()) {
                $groupCollection->wordGroups()->attach(
                    WordGroup::factory()->createdBy($groupCollection->creator)->create(),
                    ['added_by' => $groupCollection->created_by],
                );
            }
        });
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
