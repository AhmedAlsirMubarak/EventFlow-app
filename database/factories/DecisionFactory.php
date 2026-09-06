<?php

namespace Database\Factories;

use App\Models\Decision;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Decision>
 */
class DecisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'DEC-'.strtoupper(Str::random(6)),
            'event_id' => Event::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'decision_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'reason' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
