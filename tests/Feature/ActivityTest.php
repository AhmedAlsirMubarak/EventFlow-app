<?php

use App\Enums\Role as RoleEnum;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
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

test('the activity feed exposes the causer avatar url', function () {
    Storage::fake('public');
    $this->admin->update(['avatar_path' => 'avatars/admin.png']);
    Storage::disk('public')->put('avatars/admin.png', 'fake-content');

    $event = Event::factory()->create();
    $event->activityLogs()->create(['description' => 'Something happened', 'causer_id' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->get(route('activity.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has(
            'activity',
            1,
            fn (Assert $page) => $page
                ->where('actor_avatar_url', $this->admin->fresh()->avatar_url)
                ->etc()
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
