<?php

use App\Enums\DocumentCategory;
use App\Enums\DocumentVisibility;
use App\Enums\Role as RoleEnum;
use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);

    $this->event = Event::factory()->create(['created_by' => $this->member->id]);
    $this->event->users()->attach($this->member->id);
});

test('a non-member cannot upload a document', function () {
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->post(route('documents.store', $this->event), [
        'title' => 'Floor Plan',
        'category' => DocumentCategory::FloorPlan->value,
        'visibility' => DocumentVisibility::Internal->value,
        'file' => UploadedFile::fake()->create('floor-plan.pdf', 100, 'application/pdf'),
    ]);

    $response->assertForbidden();
});

test('uploading a document creates its first version and marks it current', function () {
    $response = $this->actingAs($this->member)->post(route('documents.store', $this->event), [
        'title' => 'Floor Plan',
        'category' => DocumentCategory::FloorPlan->value,
        'visibility' => DocumentVisibility::Internal->value,
        'file' => UploadedFile::fake()->create('floor-plan.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'documents']));

    $document = Document::where('title', 'Floor Plan')->firstOrFail();
    expect($document->versions)->toHaveCount(1);
    expect($document->current_version_id)->toBe($document->versions->first()->id);
    expect($document->versions->first()->version_number)->toBe(1);

    Storage::disk('local')->assertExists($document->versions->first()->disk_path);
});

test('uploading a new version increments the version number and becomes current', function () {
    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);
    $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/v1.pdf',
        'original_filename' => 'v1.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'uploaded_by' => $this->member->id,
    ]);
    $document->update(['current_version_id' => $document->versions()->first()->id]);

    $response = $this->actingAs($this->member)->post(route('documents.versions.store', $document), [
        'file' => UploadedFile::fake()->create('floor-plan-v2.pdf', 120, 'application/pdf'),
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'documents']));

    $document->refresh();
    expect($document->versions)->toHaveCount(2);
    expect($document->currentVersion->version_number)->toBe(2);
});

test('a team member can download the current version', function () {
    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/report.pdf',
        'original_filename' => 'report.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'uploaded_by' => $this->member->id,
    ]);
    Storage::disk('local')->put($version->disk_path, 'fake pdf contents');

    $response = $this->actingAs($this->member)->get(route('document-versions.download', $version));

    $response->assertOk();
});

test('a non-member cannot download a document version', function () {
    $outsider = User::factory()->create();
    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/report.pdf',
        'original_filename' => 'report.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'uploaded_by' => $this->member->id,
    ]);
    Storage::disk('local')->put($version->disk_path, 'fake pdf contents');

    $this->actingAs($outsider)->get(route('document-versions.download', $version))->assertForbidden();
});

test('deleting a document removes its files from disk', function () {
    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/report.pdf',
        'original_filename' => 'report.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'uploaded_by' => $this->member->id,
    ]);
    Storage::disk('local')->put($version->disk_path, 'fake pdf contents');

    $this->actingAs($this->member)->delete(route('documents.destroy', $document))->assertRedirect();

    expect(Document::find($document->id))->toBeNull();
    Storage::disk('local')->assertMissing($version->disk_path);
});

test('the local disk does not expose a public url for uploaded documents', function () {
    expect(config('filesystems.disks.local.driver'))->toBe('local');
    expect(config('filesystems.disks.local'))->not->toHaveKey('url');
});
