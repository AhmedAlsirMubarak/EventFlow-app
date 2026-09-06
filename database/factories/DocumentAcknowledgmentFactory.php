<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\DocumentAcknowledgment;
use App\Models\DocumentShare;
use App\Models\DocumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAcknowledgment>
 */
class DocumentAcknowledgmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_share_id' => DocumentShare::factory(),
            'contact_id' => Contact::factory(),
            'document_version_id' => DocumentVersion::factory(),
            'name' => fake()->name(),
            'organization_snapshot' => fake()->company(),
            'statement' => 'I acknowledge that I have received and reviewed this document.',
            'acknowledged_at' => now(),
        ];
    }
}
