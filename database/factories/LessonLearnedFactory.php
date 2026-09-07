<?php

namespace Database\Factories;

use App\Enums\LessonCategory;
use App\Models\Event;
use App\Models\LessonLearned;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonLearned>
 */
class LessonLearnedFactory extends Factory
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
            'category' => fake()->randomElement(LessonCategory::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
