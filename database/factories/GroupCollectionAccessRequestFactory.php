<?php

namespace Database\Factories;

use App\Enums\AccessRequestStatus;
use App\Models\GroupCollection;
use App\Models\GroupCollectionAccessRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupCollectionAccessRequest>
 */
class GroupCollectionAccessRequestFactory extends Factory
{
    /**
     * Define the model's default state: a pending request on a private item.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_collection_id' => GroupCollection::factory()->private(),
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
