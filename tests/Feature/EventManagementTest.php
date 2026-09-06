<?php

use App\Enums\OrganizationCategory;
use App\Enums\Role as RoleEnum;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->staff = User::factory()->create();
    $this->staff->assignRole(RoleEnum::GeneralStaff->value);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);
});

test('guests are redirected to login', function () {
    $this->get(route('events.index'))->assertRedirect(route('login'));
});

test('external users cannot access events', function () {
    $external = User::factory()->external()->create();

    $this->actingAs($external)->get(route('events.index'))->assertRedirect(route('portal.dashboard'));
});

test('creating an event automatically adds the creator to the team', function () {
    $response = $this->actingAs($this->staff)->post(route('events.store'), [
        'name' => 'Oman Climate Week',
        'code' => 'oman-climate-week',
        'status' => 'Planning',
    ]);

    $event = Event::where('code', 'oman-climate-week')->firstOrFail();
    $response->assertRedirect(route('events.show', $event));
    expect($event->users()->where('users.id', $this->staff->id)->exists())->toBeTrue();
});

test('a non-member, non-elevated user cannot view someone else\'s event', function () {
    $event = Event::factory()->create(['created_by' => $this->admin->id]);

    $this->actingAs($this->staff)->get(route('events.show', $event))->assertForbidden();
});

test('an assigned team member can view the event', function () {
    $event = Event::factory()->create(['created_by' => $this->admin->id]);
    $event->users()->attach($this->staff->id);

    $this->actingAs($this->staff)->get(route('events.show', $event))->assertOk();
});

test('an elevated role can view any event without being a member', function () {
    $event = Event::factory()->create(['created_by' => $this->staff->id]);

    $this->actingAs($this->admin)->get(route('events.show', $event))->assertOk();
});

test('the index only lists events the user belongs to unless elevated', function () {
    $ownEvent = Event::factory()->create(['created_by' => $this->staff->id]);
    $ownEvent->users()->attach($this->staff->id);
    Event::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->staff)->get(route('events.index'));
    $response->assertInertia(fn ($page) => $page->has('events', 1));

    $response = $this->actingAs($this->admin)->get(route('events.index'));
    $response->assertInertia(fn ($page) => $page->has('events', 2));
});

test('a team member can add another internal user to the event team', function () {
    $event = Event::factory()->create(['created_by' => $this->staff->id]);
    $event->users()->attach($this->staff->id);
    $newMember = User::factory()->create();

    $response = $this->actingAs($this->staff)->post(route('events.team.store', $event), [
        'user_id' => $newMember->id,
        'role_in_event' => RoleEnum::ProjectManager->value,
    ]);

    $response->assertRedirect(route('events.show', $event));
    expect($event->users()->where('users.id', $newMember->id)->exists())->toBeTrue();
});

test('a non-member cannot add team members', function () {
    $event = Event::factory()->create(['created_by' => $this->admin->id]);
    $newMember = User::factory()->create();

    $response = $this->actingAs($this->staff)->post(route('events.team.store', $event), [
        'user_id' => $newMember->id,
    ]);

    $response->assertForbidden();
});

test('a team member can be removed from the event', function () {
    $event = Event::factory()->create(['created_by' => $this->admin->id]);
    $event->users()->attach([$this->admin->id, $this->staff->id]);

    $response = $this->actingAs($this->admin)->delete(route('events.team.destroy', [$event, $this->staff]));

    $response->assertRedirect(route('events.show', $event));
    expect($event->users()->where('users.id', $this->staff->id)->exists())->toBeFalse();
});

test('an organization can be linked to and unlinked from an event', function () {
    $event = Event::factory()->create(['created_by' => $this->staff->id]);
    $event->users()->attach($this->staff->id);
    $organization = Organization::factory()->create();

    $this->actingAs($this->staff)->post(route('events.organizations.store', $event), [
        'organization_id' => $organization->id,
        'relationship_type' => OrganizationCategory::Sponsor->value,
    ])->assertRedirect(route('events.show', $event));

    expect($event->organizations()->where('organizations.id', $organization->id)->exists())->toBeTrue();

    $this->actingAs($this->staff)->delete(route('events.organizations.destroy', [$event, $organization]))
        ->assertRedirect(route('events.show', $event));

    expect($event->organizations()->where('organizations.id', $organization->id)->exists())->toBeFalse();
});

test('general staff cannot delete an event', function () {
    $event = Event::factory()->create(['created_by' => $this->staff->id]);
    $event->users()->attach($this->staff->id);

    $this->actingAs($this->staff)->delete(route('events.destroy', $event))->assertForbidden();
});

test('a super administrator can delete an event', function () {
    $event = Event::factory()->create(['created_by' => $this->staff->id]);

    $this->actingAs($this->admin)->delete(route('events.destroy', $event))
        ->assertRedirect(route('events.index'));

    expect(Event::find($event->id))->toBeNull();
});

test('event end date must be on or after the start date', function () {
    $response = $this->actingAs($this->staff)->post(route('events.store'), [
        'name' => 'Bad Dates Event',
        'code' => 'bad-dates-event',
        'status' => 'Planning',
        'start_date' => '2027-05-10',
        'end_date' => '2027-05-01',
    ]);

    $response->assertSessionHasErrors('end_date');
});
