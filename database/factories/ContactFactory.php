<?php

namespace Database\Factories;

use App\Enums\PreferredChannel;
use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'full_name' => fake()->name(),
            'job_title' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Operations', 'Marketing', 'Finance', 'Communications']),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->phoneNumber(),
            'preferred_channel' => fake()->randomElement(PreferredChannel::cases()),
        ];
    }
}
