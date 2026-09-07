<?php

use App\Enums\Role as RoleEnum;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);

    $this->event = Event::factory()->create(['created_by' => $this->member->id, 'status' => 'Active']);
    $this->event->users()->attach([$this->member->id, $this->admin->id]);
});

test('a general staff member cannot close an event', function () {
    $this->actingAs($this->member)->post(route('events.close', $this->event), [
        'closure_summary' => 'All done.',
    ])->assertForbidden();
});

test('an elevated role can close an event', function () {
    $response = $this->actingAs($this->admin)->post(route('events.close', $this->event), [
        'closure_summary' => 'Event completed successfully with no outstanding items.',
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'closure']));

    $this->event->refresh();
    expect($this->event->status->value)->toBe('Completed');
    expect($this->event->closed_by)->toBe($this->admin->id);
    expect($this->event->closed_at)->not->toBeNull();
    expect($this->event->closure_summary)->toBe('Event completed successfully with no outstanding items.');
});

test('closing an event does not require a summary', function () {
    $this->actingAs($this->admin)->post(route('events.close', $this->event), [])
        ->assertRedirect();

    expect($this->event->refresh()->status->value)->toBe('Completed');
});

test('an elevated role can reopen a closed event', function () {
    $this->actingAs($this->admin)->post(route('events.close', $this->event), [
        'closure_summary' => 'Wrapped up.',
    ]);

    $response = $this->actingAs($this->admin)->post(route('events.reopen', $this->event));
    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'closure']));

    $this->event->refresh();
    expect($this->event->status->value)->toBe('Active');
    expect($this->event->closed_at)->toBeNull();
    expect($this->event->closed_by)->toBeNull();
    expect($this->event->closure_summary)->toBeNull();
});

test('a general staff member cannot reopen an event', function () {
    $this->actingAs($this->admin)->post(route('events.close', $this->event), []);

    $this->actingAs($this->member)->post(route('events.reopen', $this->event))
        ->assertForbidden();
});
