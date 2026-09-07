<?php

use App\Enums\Role as RoleEnum;
use App\Models\AbstractCall;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);

    $this->event = Event::factory()->create();
    $this->event->users()->attach([$this->member->id, $this->admin->id]);
});

test('a general staff member cannot create a call for abstracts', function () {
    $this->actingAs($this->member)->post(route('abstract-calls.store', $this->event), [
        'instructions' => 'Submit your talk proposal.',
    ])->assertForbidden();
});

test('an elevated role can create a call for abstracts with a unique public token', function () {
    $response = $this->actingAs($this->admin)->post(route('abstract-calls.store', $this->event), [
        'instructions' => 'Submit your talk proposal.',
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'abstracts']));

    $call = AbstractCall::where('event_id', $this->event->id)->firstOrFail();
    expect($call->instructions)->toBe('Submit your talk proposal.');
    expect($call->public_token)->not->toBeEmpty();
    expect(strlen($call->public_token))->toBe(40);
    expect($call->is_open)->toBeTrue();
});

test('an event cannot have two calls for abstracts', function () {
    AbstractCall::factory()->create(['event_id' => $this->event->id]);

    $response = $this->actingAs($this->admin)->post(route('abstract-calls.store', $this->event), [
        'instructions' => 'Second call.',
    ]);

    $response->assertSessionHasErrors('instructions');
    expect(AbstractCall::where('event_id', $this->event->id)->count())->toBe(1);
});

test('closing dates cannot be before opening dates', function () {
    $this->actingAs($this->admin)->post(route('abstract-calls.store', $this->event), [
        'opens_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'closes_at' => now()->format('Y-m-d H:i:s'),
    ])->assertSessionHasErrors('closes_at');
});

test('an elevated role can update the call and toggle it closed', function () {
    $call = AbstractCall::factory()->create(['event_id' => $this->event->id, 'is_open' => true]);

    $response = $this->actingAs($this->admin)->put(route('abstract-calls.update', $call), [
        'instructions' => 'Updated instructions.',
        'is_open' => false,
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'abstracts']));

    $call->refresh();
    expect($call->instructions)->toBe('Updated instructions.');
    expect($call->is_open)->toBeFalse();
});
