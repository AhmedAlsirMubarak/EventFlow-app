<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
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
            'subject_type' => Event::class,
            'subject_id' => 1,
            'description' => fake()->sentence(),
        ];
    }
}
