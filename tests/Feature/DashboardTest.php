<?php

use App\Enums\EventStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
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
            ->etc()
        )
    );
});

test('dashboard stats are cached per filter combination and expire on their own', function () {
    Cache::flush();
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::SuperAdministrator->value);

    $first = $this->actingAs($admin)->get('/dashboard');
    $first->assertInertia(fn (Assert $page) => $page->where('stats.active_events', 0));

    // A new active event won't be reflected while the same filter combination is
    // still cached — proves the DashboardController query only ran once.
    Event::factory()->create(['status' => EventStatus::Active]);

    $stillCached = $this->actingAs($admin)->get('/dashboard');
    $stillCached->assertInertia(fn (Assert $page) => $page->where('stats.active_events', 0));

    // A different filter combination is a different cache key, so it computes fresh.
    $otherEvent = Event::factory()->create();
    $differentFilters = $this->actingAs($admin)->get('/dashboard?event_id='.$otherEvent->id);
    $differentFilters->assertInertia(fn (Assert $page) => $page->where('stats.active_events', 1));

    // Once the cache entry is cleared (simulating TTL expiry), the original
    // filter combination picks up the new data.
    Cache::flush();
    $afterExpiry = $this->actingAs($admin)->get('/dashboard');
    $afterExpiry->assertInertia(fn (Assert $page) => $page->where('stats.active_events', 1));
});
