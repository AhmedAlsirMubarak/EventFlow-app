<?php

namespace Database\Factories;

use App\Enums\CommitteeRole;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMember>
 */
class CommitteeMemberFactory extends Factory
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
            'user_id' => User::factory(),
            'committee_role' => CommitteeRole::Reviewer,
        ];
    }
}
