<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // Only seed a login-ready demo Super Administrator for local development —
        // never ship a well-known account/password combination to a real environment.
        if (app()->environment('local')) {
            $admin = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => env('SEED_ADMIN_PASSWORD', 'password'),
            ]);

            $admin->assignRole(RoleEnum::SuperAdministrator->value);
        }
    }
}
