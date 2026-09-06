<?php

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
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
            'description' => fake()->optional()->sentence(),
            'category' => fake()->randomElement(DocumentCategory::cases()),
            'visibility' => DocumentVisibility::Internal,
            'acknowledgment_required' => false,
            'created_by' => User::factory(),
        ];
    }
}
