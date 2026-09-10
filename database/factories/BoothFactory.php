<?php

namespace Database\Factories;

use App\Models\Booth;
use App\Models\FloorPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booth>
 */
class BoothFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'floor_plan_id' => FloorPlan::factory(),
            'booth_number' => strtoupper(fake()->unique()->bothify('?#')),
            'x' => fake()->randomFloat(2, 0, 80),
            'y' => fake()->randomFloat(2, 0, 80),
            'width' => fake()->randomFloat(2, 5, 15),
            'height' => fake()->randomFloat(2, 5, 15),
        ];
    }
}
