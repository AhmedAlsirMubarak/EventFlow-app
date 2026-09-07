<?php

namespace Database\Factories;

use App\Enums\ReviewRecommendation;
use App\Models\AbstractReview;
use App\Models\AbstractSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AbstractReview>
 */
class AbstractReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'abstract_submission_id' => AbstractSubmission::factory(),
            'user_id' => User::factory(),
            'score' => fake()->numberBetween(1, 5),
            'recommendation' => fake()->randomElement(ReviewRecommendation::cases()),
        ];
    }
}
