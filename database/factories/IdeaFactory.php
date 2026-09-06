<?php

namespace Database\Factories;

use App\Enums\IdeaStatus;
use App\Models\Event;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Idea>
 */
class IdeaFactory extends Factory
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
            'category' => fake()->randomElement(['Marketing', 'Operations', 'Sponsorship', 'Technology']),
            'status' => IdeaStatus::New,
            'created_by' => User::factory(),
        ];
    }
}
