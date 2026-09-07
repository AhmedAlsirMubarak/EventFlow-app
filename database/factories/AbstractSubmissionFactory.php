<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\AbstractCall;
use App\Models\AbstractSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AbstractSubmission>
 */
class AbstractSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'abstract_call_id' => AbstractCall::factory(),
            'reference' => 'ABS-'.strtoupper(Str::random(6)),
            'title' => fake()->sentence(5),
            'abstract_body' => fake()->paragraphs(2, true),
            'speaker_name' => fake()->name(),
            'speaker_email' => fake()->unique()->safeEmail(),
            'status' => SubmissionStatus::Submitted,
            'public_token' => Str::random(40),
            'submitted_at' => now(),
        ];
    }
}
