<?php

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('authenticated users see the dashboard with accurate stats', function () {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::SuperAdministrator->value);
    User::factory()->count(2)->create();
    User::factory()->external()->create();

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('stats', fn (Assert $page) => $page
            ->where('total_users', 4)
            ->where('internal_users', 3)
            ->where('external_users', 1)
            ->where('roles_configured', 12)
        )
    );
});
