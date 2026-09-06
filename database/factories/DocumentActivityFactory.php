<?php

namespace Database\Factories;

use App\Enums\DocumentActivityType;
use App\Models\Document;
use App\Models\DocumentActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentActivity>
 */
class DocumentActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'type' => fake()->randomElement(DocumentActivityType::cases()),
        ];
    }
}
