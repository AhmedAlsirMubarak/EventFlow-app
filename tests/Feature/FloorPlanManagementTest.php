<?php

use App\Enums\Role as RoleEnum;
use App\Models\Booth;
use App\Models\Event;
use App\Models\FloorPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->outsider = User::factory()->create();
    $this->outsider->assignRole(RoleEnum::GeneralStaff->value);

    $this->event = Event::factory()->create();
    $this->event->users()->attach($this->member->id);
});

test('an event team member can create a floor plan with an image', function () {
    $image = UploadedFile::fake()->image('venue.png', 1000, 800)->size(500);

    $response = $this->actingAs($this->member)->post(route('floor-plans.store', $this->event), [
        'name' => 'Hall A',
        'image' => $image,
    ]);

    $response->assertRedirect();

    $floorPlan = FloorPlan::where('event_id', $this->event->id)->firstOrFail();
    expect($floorPlan->name)->toBe('Hall A');
    expect($floorPlan->created_by)->toBe($this->member->id);
    expect($floorPlan->image_path)->not->toBeNull();
    expect($floorPlan->image_url)->not->toBeNull();
    Storage::disk('public')->assertExists($floorPlan->image_path);
});

test('a floor plan can be created without an image', function () {
    $this->actingAs($this->member)->post(route('floor-plans.store', $this->event), [
        'name' => 'Hall B',
    ])->assertRedirect();

    $floorPlan = FloorPlan::where('event_id', $this->event->id)->firstOrFail();
    expect($floorPlan->image_path)->toBeNull();
});

test('a non-team member cannot create a floor plan', function () {
    $this->actingAs($this->outsider)->post(route('floor-plans.store', $this->event), [
        'name' => 'Hall A',
    ])->assertForbidden();
});

test('a non-image file is rejected', function () {
    $file = UploadedFile::fake()->create('not-an-image.pdf', 100, 'application/pdf');

    $this->actingAs($this->member)->post(route('floor-plans.store', $this->event), [
        'name' => 'Hall A',
        'image' => $file,
    ])->assertSessionHasErrors('image');
});

test('uploading a new image replaces and deletes the old file', function () {
    $floorPlan = FloorPlan::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'image_path' => 'floor-plans/old.png',
    ]);
    Storage::disk('public')->put('floor-plans/old.png', 'fake-content');

    $newImage = UploadedFile::fake()->image('new-venue.png');

    $this->actingAs($this->member)->put(route('floor-plans.update', $floorPlan), [
        'name' => $floorPlan->name,
        'image' => $newImage,
    ]);

    $floorPlan->refresh();
    Storage::disk('public')->assertMissing('floor-plans/old.png');
    Storage::disk('public')->assertExists($floorPlan->image_path);
});

test('the image can be removed without uploading a new one', function () {
    $floorPlan = FloorPlan::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'image_path' => 'floor-plans/current.png',
    ]);
    Storage::disk('public')->put('floor-plans/current.png', 'fake-content');

    $this->actingAs($this->member)->put(route('floor-plans.update', $floorPlan), [
        'name' => $floorPlan->name,
        'remove_image' => true,
    ]);

    $floorPlan->refresh();
    expect($floorPlan->image_path)->toBeNull();
    Storage::disk('public')->assertMissing('floor-plans/current.png');
});

test('deleting a floor plan removes its image file and its booths', function () {
    $floorPlan = FloorPlan::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'image_path' => 'floor-plans/to-delete.png',
    ]);
    Storage::disk('public')->put('floor-plans/to-delete.png', 'fake-content');
    $booth = $floorPlan->booths()->create([
        'booth_number' => 'A1', 'x' => 1, 'y' => 1, 'width' => 5, 'height' => 5,
    ]);

    $this->actingAs($this->member)->delete(route('floor-plans.destroy', $floorPlan))->assertRedirect();

    Storage::disk('public')->assertMissing('floor-plans/to-delete.png');
    expect(FloorPlan::find($floorPlan->id))->toBeNull();
    expect(Booth::find($booth->id))->toBeNull();
});

test('deleting an event removes its floor plan image files', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::SuperAdministrator->value);

    $floorPlan = FloorPlan::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'image_path' => 'floor-plans/event-delete.png',
    ]);
    Storage::disk('public')->put('floor-plans/event-delete.png', 'fake-content');

    $this->actingAs($admin)->delete(route('events.destroy', $this->event));

    Storage::disk('public')->assertMissing('floor-plans/event-delete.png');
});
