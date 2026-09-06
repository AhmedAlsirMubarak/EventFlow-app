<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
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
            'title' => fake()->sentence(3),
            'held_at' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'location' => fake()->randomElement(['Online (Teams)', 'Head Office - Room 2', 'Venue Site Visit']),
            'agenda' => fake()->optional()->paragraph(),
            'discussion_notes' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
