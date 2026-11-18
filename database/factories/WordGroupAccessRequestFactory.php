<?php

namespace Database\Factories;

use App\Enums\AccessRequestStatus;
use App\Models\User;
use App\Models\WordGroup;
use App\Models\WordGroupAccessRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WordGroupAccessRequest>
 */
class WordGroupAccessRequestFactory extends Factory
{
    /**
     * Define the model's default state: a pending request on a private item.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'word_group_id' => WordGroup::factory()->private(),
            'user_id' => User::factory(),
            'status' => AccessRequestStatus::Pending,
        ];
    }

    public function refused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccessRequestStatus::Refused,
            'decided_at' => now(),
        ]);
    }

    public function by(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->getKey(),
        ]);
    }
}
