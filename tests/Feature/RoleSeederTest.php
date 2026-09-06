<?php

use App\Enums\Role as RoleEnum;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

test('role seeder creates every role defined in the role enum', function () {
    $this->seed(RoleSeeder::class);

    expect(Role::count())->toBe(count(RoleEnum::cases()));

    foreach (RoleEnum::cases() as $role) {
        expect(Role::where('name', $role->value)->exists())->toBeTrue();
    }
});

test('role seeder is idempotent when run twice', function () {
    $this->seed(RoleSeeder::class);
    $this->seed(RoleSeeder::class);

    expect(Role::count())->toBe(count(RoleEnum::cases()));
});
