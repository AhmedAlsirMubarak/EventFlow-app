<?php

use App\Enums\Role as RoleEnum;
use App\Models\Booth;
use App\Models\Event;
use App\Models\FloorPlan;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->outsider = User::factory()->create();
    $this->outsider->assignRole(RoleEnum::GeneralStaff->value);

    $this->event = Event::factory()->create();
    $this->event->users()->attach($this->member->id);

    $this->floorPlan = FloorPlan::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);

    $this->organization = Organization::factory()->create();
    $this->event->organizations()->attach($this->organization->id);
});

test('a team member can add a booth to the floor plan', function () {
    $response = $this->actingAs($this->member)->post(route('booths.store', $this->floorPlan), [
        'booth_number' => 'A1',
        'x' => 10, 'y' => 15, 'width' => 8, 'height' => 6,
    ]);

    $response->assertRedirect();

    $booth = Booth::where('floor_plan_id', $this->floorPlan->id)->firstOrFail();
    expect($booth->booth_number)->toBe('A1');
    expect((float) $booth->x)->toBe(10.0);
    expect($booth->organization_id)->toBeNull();
});

test('a booth can be created already assigned to a participating organization', function () {
    $this->actingAs($this->member)->post(route('booths.store', $this->floorPlan), [
        'booth_number' => 'A2',
        'organization_id' => $this->organization->id,
        'x' => 10, 'y' => 15, 'width' => 8, 'height' => 6,
    ])->assertRedirect();

    $booth = Booth::where('booth_number', 'A2')->firstOrFail();
    expect($booth->organization_id)->toBe($this->organization->id);
});

test('a booth cannot be assigned to an organization not participating in the event', function () {
    $otherOrganization = Organization::factory()->create();

    $this->actingAs($this->member)->post(route('booths.store', $this->floorPlan), [
        'booth_number' => 'A3',
        'organization_id' => $otherOrganization->id,
        'x' => 10, 'y' => 15, 'width' => 8, 'height' => 6,
    ])->assertSessionHasErrors('organization_id');
});

test('booth numbers must be unique within a floor plan', function () {
    Booth::factory()->create(['floor_plan_id' => $this->floorPlan->id, 'booth_number' => 'A1']);

    $this->actingAs($this->member)->post(route('booths.store', $this->floorPlan), [
        'booth_number' => 'A1',
        'x' => 1, 'y' => 1, 'width' => 5, 'height' => 5,
    ])->assertSessionHasErrors('booth_number');
});

test('the same booth number is allowed on a different floor plan', function () {
    Booth::factory()->create(['floor_plan_id' => $this->floorPlan->id, 'booth_number' => 'A1']);
    $otherFloorPlan = FloorPlan::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $this->actingAs($this->member)->post(route('booths.store', $otherFloorPlan), [
        'booth_number' => 'A1',
        'x' => 1, 'y' => 1, 'width' => 5, 'height' => 5,
    ])->assertSessionHasNoErrors();
});

test('a booth position and size can be updated', function () {
    $booth = Booth::factory()->create(['floor_plan_id' => $this->floorPlan->id, 'booth_number' => 'A1']);

    $this->actingAs($this->member)->put(route('booths.update', $booth), [
        'booth_number' => 'A1',
        'x' => 25.5, 'y' => 30.25, 'width' => 12, 'height' => 9,
    ])->assertRedirect();

    $booth->refresh();
    expect((float) $booth->x)->toBe(25.5);
    expect((float) $booth->y)->toBe(30.25);
});

test('a booth can be reassigned to a different participating organization', function () {
    $booth = Booth::factory()->create([
        'floor_plan_id' => $this->floorPlan->id,
        'booth_number' => 'A1',
        'organization_id' => $this->organization->id,
    ]);
    $newOrganization = Organization::factory()->create();
    $this->event->organizations()->attach($newOrganization->id);

    $this->actingAs($this->member)->put(route('booths.update', $booth), [
        'booth_number' => 'A1',
        'organization_id' => $newOrganization->id,
        'x' => 1, 'y' => 1, 'width' => 5, 'height' => 5,
    ]);

    expect($booth->refresh()->organization_id)->toBe($newOrganization->id);
});

test('a booth can be unassigned', function () {
    $booth = Booth::factory()->create([
        'floor_plan_id' => $this->floorPlan->id,
        'booth_number' => 'A1',
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->member)->put(route('booths.update', $booth), [
        'booth_number' => 'A1',
        'organization_id' => null,
        'x' => 1, 'y' => 1, 'width' => 5, 'height' => 5,
    ]);

    expect($booth->refresh()->organization_id)->toBeNull();
});

test('a booth can be removed', function () {
    $booth = Booth::factory()->create(['floor_plan_id' => $this->floorPlan->id, 'booth_number' => 'A1']);

    $this->actingAs($this->member)->delete(route('booths.destroy', $booth))->assertRedirect();

    expect(Booth::find($booth->id))->toBeNull();
});

test('a non-team member cannot manage booths', function () {
    $booth = Booth::factory()->create(['floor_plan_id' => $this->floorPlan->id, 'booth_number' => 'A1']);

    $this->actingAs($this->outsider)->post(route('booths.store', $this->floorPlan), [
        'booth_number' => 'A2', 'x' => 1, 'y' => 1, 'width' => 5, 'height' => 5,
    ])->assertForbidden();

    $this->actingAs($this->outsider)->put(route('booths.update', $booth), [
        'booth_number' => 'A1', 'x' => 1, 'y' => 1, 'width' => 5, 'height' => 5,
    ])->assertForbidden();

    $this->actingAs($this->outsider)->delete(route('booths.destroy', $booth))->assertForbidden();
});

test('unassigning an organization from the event nulls out any booths assigned to it', function () {
    $booth = Booth::factory()->create([
        'floor_plan_id' => $this->floorPlan->id,
        'booth_number' => 'A1',
        'organization_id' => $this->organization->id,
    ]);

    $this->organization->delete();

    expect($booth->refresh()->organization_id)->toBeNull();
});
