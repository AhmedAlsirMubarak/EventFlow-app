<?php

use App\Enums\EventStatus;
use App\Enums\Role as RoleEnum;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Task;
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
    $otherEvent = Event::factory()->create(['status' => EventStatus::Planning]);
    $differentFilters = $this->actingAs($admin)->get('/dashboard?event_id='.$otherEvent->id);
    $differentFilters->assertInertia(fn (Assert $page) => $page->where('stats.active_events', 1));

    // Once the cache entry is cleared (simulating TTL expiry), the original
    // filter combination picks up the new data.
    Cache::flush();
    $afterExpiry = $this->actingAs($admin)->get('/dashboard');
    $afterExpiry->assertInertia(fn (Assert $page) => $page->where('stats.active_events', 1));
});

test('the task chart lists every status in workflow order, including empty ones', function () {
    Cache::flush();
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::SuperAdministrator->value);
    $event = Event::factory()->create();
    Task::factory()->count(2)->create(['event_id' => $event->id, 'status' => TaskStatus::InProgress]);
    Task::factory()->create(['event_id' => $event->id, 'status' => TaskStatus::Completed]);

    $this->actingAs($admin)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('charts.tasks_by_status', [
            ['status' => 'Backlog', 'count' => 0],
            ['status' => 'Planned', 'count' => 0],
            ['status' => 'In Progress', 'count' => 2],
            ['status' => 'Waiting / Blocked', 'count' => 0],
            ['status' => 'Completed', 'count' => 1],
        ])
        ->where('stats.open_tasks', 2)
    );
});

test('the activity trend covers the last 30 days, zeros included', function () {
    Cache::flush();
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::SuperAdministrator->value);
    $event = Event::factory()->create();
    ActivityLog::factory()->count(3)->create(['event_id' => $event->id, 'created_at' => now()]);
    ActivityLog::factory()->create(['event_id' => $event->id, 'created_at' => now()->subDays(40)]);

    $this->actingAs($admin)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->has('charts.activity_trend', 30)
        ->where('charts.activity_trend.29.date', now()->toDateString())
        ->where('charts.activity_trend.29.count', 3)
        ->where('charts.activity_trend.0.count', 0)
    );
});

test('the needs-attention and upcoming lists only show events you can open', function () {
    Cache::flush();
    $this->seed(RoleSeeder::class);
    $staff = User::factory()->create();
    $staff->assignRole(RoleEnum::GeneralStaff->value);

    $mine = Event::factory()->create(['status' => EventStatus::Planning, 'start_date' => now()->addDays(10), 'end_date' => now()->addDays(12)]);
    $mine->users()->attach($staff->id);
    $notMine = Event::factory()->create(['status' => EventStatus::Planning, 'start_date' => now()->addDays(5), 'end_date' => now()->addDays(6)]);
    $finished = Event::factory()->create(['status' => EventStatus::Active, 'start_date' => now()->subDays(10), 'end_date' => now()->subDays(8)]);
    $finished->users()->attach($staff->id);

    $overdue = Task::factory()->create(['event_id' => $mine->id, 'status' => TaskStatus::Planned, 'due_date' => now()->subDay()]);
    Task::factory()->create(['event_id' => $mine->id, 'status' => TaskStatus::Completed, 'due_date' => now()->subDay()]);
    Task::factory()->create(['event_id' => $mine->id, 'status' => TaskStatus::Planned, 'due_date' => now()->addDays(20)]);
    Task::factory()->create(['event_id' => $notMine->id, 'status' => TaskStatus::Planned, 'due_date' => now()->subDay()]);

    $this->actingAs($staff)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->has('upcomingEvents', 1)
        ->where('upcomingEvents.0.id', $mine->id)
        ->has('attentionTasks', 1)
        ->where('attentionTasks.0.id', $overdue->id)
    );
});
