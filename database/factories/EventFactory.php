<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city().' '.fake()->randomElement(['Summit', 'Conference', 'Forum', 'Week', 'Expo']).' '.fake()->year();

        return [
            'name' => $name,
            'code' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->optional()->paragraph(),
            'start_date' => fake()->dateTimeBetween('now', '+6 months'),
            'end_date' => fake()->dateTimeBetween('+6 months', '+7 months'),
            'status' => fake()->randomElement(EventStatus::cases()),
            'created_by' => User::factory(),
        ];
    }
}
