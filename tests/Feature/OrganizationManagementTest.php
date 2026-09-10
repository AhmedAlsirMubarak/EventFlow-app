<?php

use App\Enums\OrganizationCategory;
use App\Enums\Role as RoleEnum;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

    $this->actingAs($external)->get(route('organizations.index'))->assertRedirect(route('portal.dashboard'));
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

test('a logo can be uploaded when creating an organization', function () {
    Storage::fake('public');
    $logo = UploadedFile::fake()->image('logo.png', 200, 200)->size(100);

    $this->actingAs($this->staff)->post(route('organizations.store'), [
        'name' => 'Logo Org',
        'category' => OrganizationCategory::GovernmentMinistry->value,
        'logo' => $logo,
    ])->assertRedirect();

    $organization = Organization::where('name', 'Logo Org')->firstOrFail();
    expect($organization->logo_path)->not->toBeNull();
    expect($organization->logo_url)->not->toBeNull();
    Storage::disk('public')->assertExists($organization->logo_path);
});

test('an svg is rejected as an organization logo', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml');

    $this->actingAs($this->staff)->post(route('organizations.store'), [
        'name' => 'SVG Logo Org',
        'category' => OrganizationCategory::GovernmentMinistry->value,
        'logo' => $file,
    ])->assertSessionHasErrors('logo');

    expect(Organization::where('name', 'SVG Logo Org')->exists())->toBeFalse();
});

test('uploading a new organization logo replaces and deletes the old file', function () {
    Storage::fake('public');
    $organization = Organization::factory()->create(['logo_path' => 'organization-logos/old.png']);
    Storage::disk('public')->put('organization-logos/old.png', 'fake-content');

    $newLogo = UploadedFile::fake()->image('new-logo.png');

    $this->actingAs($this->staff)->put(route('organizations.update', $organization), [
        'name' => $organization->name,
        'category' => $organization->category->value,
        'logo' => $newLogo,
    ]);

    $organization->refresh();
    Storage::disk('public')->assertMissing('organization-logos/old.png');
    Storage::disk('public')->assertExists($organization->logo_path);
});

test('an organization logo can be removed without uploading a new one', function () {
    Storage::fake('public');
    $organization = Organization::factory()->create(['logo_path' => 'organization-logos/current.png']);
    Storage::disk('public')->put('organization-logos/current.png', 'fake-content');

    $this->actingAs($this->staff)->put(route('organizations.update', $organization), [
        'name' => $organization->name,
        'category' => $organization->category->value,
        'remove_logo' => true,
    ]);

    $organization->refresh();
    expect($organization->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing('organization-logos/current.png');
});

test('the organization logo file is deleted when the organization is deleted', function () {
    Storage::fake('public');
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::SuperAdministrator->value);

    $organization = Organization::factory()->create(['logo_path' => 'organization-logos/to-delete.png']);
    Storage::disk('public')->put('organization-logos/to-delete.png', 'fake-content');

    $this->actingAs($admin)->delete(route('organizations.destroy', $organization));

    Storage::disk('public')->assertMissing('organization-logos/to-delete.png');
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
