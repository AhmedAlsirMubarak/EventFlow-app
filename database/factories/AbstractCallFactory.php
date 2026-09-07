<?php

namespace Database\Factories;

use App\Models\AbstractCall;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AbstractCall>
 */
class AbstractCallFactory extends Factory
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
            'instructions' => fake()->optional()->paragraph(),
            'is_open' => true,
            'public_token' => Str::random(40),
        ];
    }
}
