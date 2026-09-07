<?php

namespace Database\Factories;

use App\Models\AbstractCall;
use App\Models\AbstractTrack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AbstractTrack>
 */
class AbstractTrackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'abstract_call_id' => AbstractCall::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
