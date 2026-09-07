<?php

namespace Database\Factories;

use App\Enums\ReminderStatus;
use App\Models\Event;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
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
            'notes' => fake()->optional()->paragraph(),
            'remind_at' => fake()->dateTimeBetween('now', '+2 weeks'),
            'status' => ReminderStatus::Pending,
        ];
    }
}
