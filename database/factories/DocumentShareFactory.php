<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentShare>
 */
class DocumentShareFactory extends Factory
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
            'shared_by' => User::factory(),
        ];
    }
}
