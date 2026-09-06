<?php

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    Route::middleware(['web', 'auth', 'role:'.RoleEnum::SuperAdministrator->value])
        ->get('/_test/admin-only', fn () => 'ok');
});

test('a user without the required role is forbidden', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/_test/admin-only');

    $response->assertForbidden();
});

test('a user with the required role is allowed through', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::SuperAdministrator->value);

    $response = $this->actingAs($user)->get('/_test/admin-only');

    $response->assertOk();
    $response->assertSee('ok');
});

test('a guest is redirected to login rather than seeing the protected route', function () {
    $response = $this->get('/_test/admin-only');

    $response->assertRedirect(route('login'));
});
