<?php

use App\Enums\Role as RoleEnum;
use App\Models\AbstractCall;
use App\Models\AbstractTrack;
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
    $this->call = AbstractCall::factory()->create(['event_id' => $this->event->id]);
});

test('a general staff member cannot add a track', function () {
    $this->actingAs($this->member)->post(route('abstract-tracks.store', $this->call), [
        'name' => 'AI & Machine Learning',
    ])->assertForbidden();
});

test('an elevated role can add, update, and remove a track', function () {
    $response = $this->actingAs($this->admin)->post(route('abstract-tracks.store', $this->call), [
        'name' => 'AI & Machine Learning',
        'description' => 'Talks about applied AI.',
    ]);
    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'abstracts']));

    $track = AbstractTrack::where('abstract_call_id', $this->call->id)->firstOrFail();
    expect($track->name)->toBe('AI & Machine Learning');

    $this->actingAs($this->admin)->put(route('abstract-tracks.update', $track), [
        'name' => 'Artificial Intelligence',
    ])->assertRedirect();
    expect($track->refresh()->name)->toBe('Artificial Intelligence');

    $this->actingAs($this->admin)->delete(route('abstract-tracks.destroy', $track))->assertRedirect();
    expect(AbstractTrack::find($track->id))->toBeNull();
});

test('adding a track requires a name', function () {
    $this->actingAs($this->admin)->post(route('abstract-tracks.store', $this->call), [])
        ->assertSessionHasErrors('name');
});
