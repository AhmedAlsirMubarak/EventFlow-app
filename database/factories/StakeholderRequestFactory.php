<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Enums\TaskPriority;
use App\Models\Event;
use App\Models\StakeholderRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StakeholderRequest>
 */
class StakeholderRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'status' => RequestStatus::Requested,
        ];
    }
}
