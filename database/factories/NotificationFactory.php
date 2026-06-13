<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'title' => fake()->sentence(4),
            'body' => fake()->sentence(12),
            'type' => fake()->randomElement([
                Notification::TYPE_INFO,
                Notification::TYPE_SUCCESS,
                Notification::TYPE_WARNING,
                Notification::TYPE_DANGER,
            ]),
            'read_at' => null,
        ];
    }

    /**
     * Indicate that the notification belongs to a specific user (not a broadcast).
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the notification has already been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
