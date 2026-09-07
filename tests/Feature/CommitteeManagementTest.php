<?php

use App\Enums\Role as RoleEnum;
use App\Models\CommitteeMember;
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

test('a general staff member cannot add a committee member', function () {
    $reviewer = User::factory()->create();

    $this->actingAs($this->member)->post(route('committee.store', $this->event), [
        'user_id' => $reviewer->id,
        'committee_role' => 'Reviewer',
    ])->assertForbidden();
});

test('an elevated role can add a committee member', function () {
    $reviewer = User::factory()->create();

    $response = $this->actingAs($this->admin)->post(route('committee.store', $this->event), [
        'user_id' => $reviewer->id,
        'committee_role' => 'Reviewer',
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'abstracts']));

    $committeeMember = CommitteeMember::where('event_id', $this->event->id)->where('user_id', $reviewer->id)->firstOrFail();
    expect($committeeMember->committee_role->value)->toBe('Reviewer');
});

test('a committee chair can add another committee member', function () {
    $chair = User::factory()->create();
    $this->event->users()->attach($chair->id);
    CommitteeMember::factory()->create(['event_id' => $this->event->id, 'user_id' => $chair->id, 'committee_role' => 'Chair']);

    $reviewer = User::factory()->create();

    $this->actingAs($chair)->post(route('committee.store', $this->event), [
        'user_id' => $reviewer->id,
        'committee_role' => 'Reviewer',
    ])->assertRedirect();

    expect(CommitteeMember::where('user_id', $reviewer->id)->exists())->toBeTrue();
});

test('a plain reviewer cannot add another committee member', function () {
    $reviewer = User::factory()->create();
    $this->event->users()->attach($reviewer->id);
    CommitteeMember::factory()->create(['event_id' => $this->event->id, 'user_id' => $reviewer->id, 'committee_role' => 'Reviewer']);

    $another = User::factory()->create();

    $this->actingAs($reviewer)->post(route('committee.store', $this->event), [
        'user_id' => $another->id,
        'committee_role' => 'Reviewer',
    ])->assertForbidden();
});

test('re-adding an existing committee member updates their role instead of duplicating', function () {
    $user = User::factory()->create();
    CommitteeMember::factory()->create(['event_id' => $this->event->id, 'user_id' => $user->id, 'committee_role' => 'Reviewer']);

    $this->actingAs($this->admin)->post(route('committee.store', $this->event), [
        'user_id' => $user->id,
        'committee_role' => 'Chair',
    ]);

    expect(CommitteeMember::where('event_id', $this->event->id)->where('user_id', $user->id)->count())->toBe(1);
    expect(CommitteeMember::where('event_id', $this->event->id)->where('user_id', $user->id)->first()->committee_role->value)->toBe('Chair');
});

test('an elevated role can remove a committee member', function () {
    $committeeMember = CommitteeMember::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->admin)->delete(route('committee.destroy', $committeeMember))->assertRedirect();

    expect(CommitteeMember::find($committeeMember->id))->toBeNull();
});

test('a general staff member cannot remove a committee member', function () {
    $committeeMember = CommitteeMember::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->member)->delete(route('committee.destroy', $committeeMember))->assertForbidden();

    expect(CommitteeMember::find($committeeMember->id))->not->toBeNull();
});
