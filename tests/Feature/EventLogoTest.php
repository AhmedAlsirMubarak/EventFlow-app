<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
});

test('a logo can be uploaded when creating an event', function () {
    $logo = UploadedFile::fake()->image('logo.png', 200, 200)->size(100);

    $response = $this->actingAs($this->user)->post(route('events.store'), [
        'name' => 'Oman Climate Week',
        'code' => 'oman-climate-week',
        'status' => 'Planning',
        'logo' => $logo,
    ]);

    $response->assertRedirect();

    $event = Event::where('code', 'oman-climate-week')->firstOrFail();
    expect($event->logo_path)->not->toBeNull();
    expect($event->logo_url)->not->toBeNull();
    Storage::disk('public')->assertExists($event->logo_path);
});

test('creating an event without a logo works fine', function () {
    $this->actingAs($this->user)->post(route('events.store'), [
        'name' => 'No Logo Event',
        'code' => 'no-logo-event',
        'status' => 'Planning',
    ])->assertRedirect();

    $event = Event::where('code', 'no-logo-event')->firstOrFail();
    expect($event->logo_path)->toBeNull();
    expect($event->logo_url)->toBeNull();
});

test('a non-image file is rejected as a logo', function () {
    $file = UploadedFile::fake()->create('not-a-logo.pdf', 100, 'application/pdf');

    $this->actingAs($this->user)->post(route('events.store'), [
        'name' => 'Bad Logo Event',
        'code' => 'bad-logo-event',
        'status' => 'Planning',
        'logo' => $file,
    ])->assertSessionHasErrors('logo');

    expect(Event::where('code', 'bad-logo-event')->exists())->toBeFalse();
});

test('an svg is rejected as a logo', function () {
    // SVG can carry inline <script>, and event logos are stored on the public disk —
    // accepting one would be a stored-XSS vector served from the app's own origin.
    $file = UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml');

    $this->actingAs($this->user)->post(route('events.store'), [
        'name' => 'SVG Logo Event',
        'code' => 'svg-logo-event',
        'status' => 'Planning',
        'logo' => $file,
    ])->assertSessionHasErrors('logo');

    expect(Event::where('code', 'svg-logo-event')->exists())->toBeFalse();
});

test('uploading a new logo replaces and deletes the old file', function () {
    $event = Event::factory()->create(['logo_path' => 'event-logos/old.png']);
    Storage::disk('public')->put('event-logos/old.png', 'fake-content');
    $event->users()->attach($this->user->id);

    $newLogo = UploadedFile::fake()->image('new-logo.png');

    $this->actingAs($this->user)->put(route('events.update', $event), [
        'name' => $event->name,
        'code' => $event->code,
        'status' => $event->status->value,
        'logo' => $newLogo,
    ]);

    $event->refresh();
    Storage::disk('public')->assertMissing('event-logos/old.png');
    Storage::disk('public')->assertExists($event->logo_path);
    expect($event->logo_path)->not->toBe('event-logos/old.png');
});

test('the logo can be removed without uploading a new one', function () {
    $event = Event::factory()->create(['logo_path' => 'event-logos/current.png']);
    Storage::disk('public')->put('event-logos/current.png', 'fake-content');
    $event->users()->attach($this->user->id);

    $this->actingAs($this->user)->put(route('events.update', $event), [
        'name' => $event->name,
        'code' => $event->code,
        'status' => $event->status->value,
        'remove_logo' => true,
    ]);

    $event->refresh();
    expect($event->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing('event-logos/current.png');
});

test('the logo file is deleted when the event is deleted', function () {
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(Role::SuperAdministrator->value);

    $event = Event::factory()->create(['logo_path' => 'event-logos/to-delete.png']);
    Storage::disk('public')->put('event-logos/to-delete.png', 'fake-content');

    $this->actingAs($admin)->delete(route('events.destroy', $event));

    Storage::disk('public')->assertMissing('event-logos/to-delete.png');
});
