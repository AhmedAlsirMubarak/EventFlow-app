<?php

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);
});

test('guests are redirected to login', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('users without an administrative role are forbidden', function () {
    $staff = User::factory()->create();
    $staff->assignRole(RoleEnum::GeneralStaff->value);

    $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('admin.users.create'))->assertForbidden();
});

test('management role is also treated as administrative', function () {
    $manager = User::factory()->create();
    $manager->assignRole(RoleEnum::Management->value);

    $this->actingAs($manager)->get(route('admin.users.index'))->assertOk();
});

test('the index page only lists internal users', function () {
    User::factory()->count(2)->create();
    User::factory()->external()->create();

    $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Index')
        ->has('users', 3) // the admin plus the two internal factory users
    );
});

test('an administrator can create an internal user with roles', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'New Hire',
        'email' => 'new-hire@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [RoleEnum::ProjectManager->value],
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'new-hire@example.com')->firstOrFail();
    expect($user->isInternal())->toBeTrue();
    expect($user->hasRole(RoleEnum::ProjectManager->value))->toBeTrue();
});

test('creating a user requires matching password confirmation', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'New Hire',
        'email' => 'new-hire@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]);

    $response->assertSessionHasErrors('password');
});

test('an administrator can update a user and their roles', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::GeneralStaff->value);

    $response = $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
        'name' => 'Updated Name',
        'email' => $user->email,
        'roles' => [RoleEnum::FinanceTeam->value],
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->hasRole(RoleEnum::FinanceTeam->value))->toBeTrue();
    expect($user->hasRole(RoleEnum::GeneralStaff->value))->toBeFalse();
});

test('an administrator can reset a user password', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('an administrator can remove another user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));
    expect(User::find($user->id))->toBeNull();
});

test('an administrator cannot remove themselves', function () {
    $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin));

    $response->assertForbidden();
    expect(User::find($this->admin->id))->not->toBeNull();
});
