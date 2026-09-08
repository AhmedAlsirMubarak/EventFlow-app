<?php

use App\Enums\Role as RoleEnum;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);
});

test('the activity feed includes logged events', function () {
    $this->actingAs($this->admin)->post(route('events.store'), [
        'name' => 'Activity Test Event',
        'code' => 'activity-test-event',
        'status' => 'Planning',
    ]);

    $response = $this->actingAs($this->admin)->get(route('activity.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Activity/Index')
        ->has(
            'activity',
            1,
            fn (Assert $page) => $page->where('description', 'Event created')->etc()
        )
    );
});

test('the event filter scopes the activity feed to a single event', function () {
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();

    $eventA->activityLogs()->create(['description' => 'Event A activity']);
    $eventB->activityLogs()->create(['description' => 'Event B activity']);

    $response = $this->actingAs($this->admin)->get(route('activity.index', ['event_id' => $eventA->id]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has(
            'activity',
            1,
            fn (Assert $page) => $page->where('description', 'Event A activity')->etc()
        )
    );
});
