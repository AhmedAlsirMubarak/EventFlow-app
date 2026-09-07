<?php

use App\Enums\Role as RoleEnum;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Reminder;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);

    $this->event = Event::factory()->create(['created_by' => $this->member->id]);
    $this->event->users()->attach($this->member->id);
});

test('a non-member cannot create a reminder', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->post(route('reminders.store', $this->event), [
        'title' => 'Follow up on the venue contract',
        'remind_at' => now()->addDay()->format('Y-m-d H:i:s'),
    ])->assertForbidden();
});

test('a team member can create a reminder', function () {
    $organization = Organization::factory()->create();
    $contact = Contact::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($this->member)->post(route('reminders.store', $this->event), [
        'title' => 'Follow up on the venue contract',
        'notes' => 'Waiting on their legal team.',
        'remind_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'organization_id' => $organization->id,
        'contact_id' => $contact->id,
        'assigned_to' => $this->member->id,
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'reminders']));

    $reminder = Reminder::where('title', 'Follow up on the venue contract')->firstOrFail();
    expect($reminder->status->value)->toBe('Pending');
    expect($reminder->contact_id)->toBe($contact->id);
    expect($reminder->created_by)->toBe($this->member->id);
});

test('creating a reminder requires a title and remind_at', function () {
    $this->actingAs($this->member)->post(route('reminders.store', $this->event), [])
        ->assertSessionHasErrors(['title', 'remind_at']);
});

test('marking a reminder completed sets completed_at', function () {
    $reminder = Reminder::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $response = $this->actingAs($this->member)->put(route('reminders.update', $reminder), [
        'title' => $reminder->title,
        'remind_at' => $reminder->remind_at->format('Y-m-d H:i:s'),
        'status' => 'Completed',
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'reminders']));

    $reminder->refresh();
    expect($reminder->status->value)->toBe('Completed');
    expect($reminder->completed_at)->not->toBeNull();
});

test('reopening a completed reminder clears completed_at', function () {
    $reminder = Reminder::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'status' => 'Completed',
        'completed_at' => now(),
    ]);

    $this->actingAs($this->member)->put(route('reminders.update', $reminder), [
        'title' => $reminder->title,
        'remind_at' => $reminder->remind_at->format('Y-m-d H:i:s'),
        'status' => 'Pending',
    ]);

    expect($reminder->refresh()->completed_at)->toBeNull();
});

test('a team member cannot delete a reminder they did not create', function () {
    $reminder = Reminder::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->member)->delete(route('reminders.destroy', $reminder))->assertForbidden();
});

test('an elevated role can delete any reminder', function () {
    $reminder = Reminder::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $this->actingAs($this->admin)->delete(route('reminders.destroy', $reminder))->assertRedirect();
    expect(Reminder::find($reminder->id))->toBeNull();
});
