<?php

use App\Enums\OrganizationCategory;
use App\Enums\Role as RoleEnum;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->staff = User::factory()->create();
    $this->staff->assignRole(RoleEnum::GeneralStaff->value);
});

test('guests are redirected to login', function () {
    $this->get(route('organizations.index'))->assertRedirect(route('login'));
});

test('external users cannot access organizations', function () {
    $external = User::factory()->external()->create();

    $this->actingAs($external)->get(route('organizations.index'))->assertForbidden();
});

test('any internal user can view and create organizations', function () {
    $this->actingAs($this->staff)->get(route('organizations.index'))->assertOk();
    $this->actingAs($this->staff)->get(route('organizations.create'))->assertOk();
});

test('an internal user can create an organization', function () {
    $manager = User::factory()->create();
    $manager->assignRole(RoleEnum::AccountManager->value);

    $response = $this->actingAs($this->staff)->post(route('organizations.store'), [
        'name' => 'Ministry of Culture',
        'category' => OrganizationCategory::GovernmentMinistry->value,
        'email' => 'contact@ministry.gov',
        'account_manager_id' => $manager->id,
    ]);

    $organization = Organization::where('name', 'Ministry of Culture')->firstOrFail();
    $response->assertRedirect(route('organizations.show', $organization));
    expect($organization->account_manager_id)->toBe($manager->id);
});

test('creating an organization validates the category', function () {
    $response = $this->actingAs($this->staff)->post(route('organizations.store'), [
        'name' => 'Bad Org',
        'category' => 'Not A Real Category',
    ]);

    $response->assertSessionHasErrors('category');
});

test('an internal user can update an organization', function () {
    $organization = Organization::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($this->staff)->put(route('organizations.update', $organization), [
        'name' => 'New Name',
        'category' => $organization->category->value,
    ]);

    $response->assertRedirect(route('organizations.show', $organization));
    expect($organization->refresh()->name)->toBe('New Name');
});

test('general staff cannot delete an organization', function () {
    $organization = Organization::factory()->create();

    $this->actingAs($this->staff)->delete(route('organizations.destroy', $organization))->assertForbidden();
    expect(Organization::find($organization->id))->not->toBeNull();
});

test('a super administrator can delete an organization', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::SuperAdministrator->value);

    $organization = Organization::factory()->create();

    $this->actingAs($admin)->delete(route('organizations.destroy', $organization))->assertRedirect(route('organizations.index'));
    expect(Organization::find($organization->id))->toBeNull();
});
