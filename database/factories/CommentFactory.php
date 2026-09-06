<?php

namespace Database\Factories;

use App\Enums\CommentVisibility;
use App\Models\Comment;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commentable_type' => Idea::class,
            'commentable_id' => Idea::factory(),
            'user_id' => User::factory(),
            'visibility' => CommentVisibility::Internal,
            'body' => fake()->sentence(),
        ];
    }
}
