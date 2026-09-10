<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\FloorPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FloorPlan>
 */
class FloorPlanFactory extends Factory
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
            'name' => fake()->randomElement(['Hall A', 'Hall B', 'Level 1', 'Main Hall']),
            'created_by' => User::factory(),
        ];
    }
}
