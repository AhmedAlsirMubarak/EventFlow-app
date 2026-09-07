<?php

use App\Enums\DocumentActivityType;
use App\Enums\Role as RoleEnum;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->event = Event::factory()->create();
    $this->event->users()->attach($this->member->id);

    $this->otherEvent = Event::factory()->create();
});

test('the event page includes activity log rows scoped to that event', function () {
    ActivityLog::log($this->event, 'Idea submitted: Better signage', $this->event->id, $this->member->id);
    ActivityLog::log($this->otherEvent, 'Idea submitted: Unrelated', $this->otherEvent->id, $this->member->id);

    $response = $this->actingAs($this->member)->get(route('events.show', $this->event));

    $response->assertInertia(fn ($page) => $page
        ->has('activityLogs', 1)
        ->where('activityLogs.0.description', 'Idea submitted: Better signage')
        ->where('activityLogs.0.causer.name', $this->member->name)
    );
});

test('the event page includes document activity rows scoped to that event', function () {
    $document = Document::factory()->create(['event_id' => $this->event->id]);
    $document->activities()->create([
        'user_id' => $this->member->id,
        'type' => DocumentActivityType::Shared,
    ]);

    $otherDocument = Document::factory()->create(['event_id' => $this->otherEvent->id]);
    $otherDocument->activities()->create([
        'user_id' => $this->member->id,
        'type' => DocumentActivityType::Shared,
    ]);

    $response = $this->actingAs($this->member)->get(route('events.show', $this->event));

    $response->assertInertia(fn ($page) => $page
        ->has('documentActivities', 1)
        ->where('documentActivities.0.document.id', $document->id)
        ->where('documentActivities.0.type', 'Shared')
    );
});

test('a non-member cannot view the event page or its activity', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->get(route('events.show', $this->event))->assertForbidden();
});
