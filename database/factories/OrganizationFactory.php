<?php

namespace Database\Factories;

use App\Enums\OrganizationCategory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'category' => fake()->randomElement(OrganizationCategory::cases()),
            'website' => fake()->url(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
