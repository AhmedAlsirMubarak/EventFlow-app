<?php

use App\Enums\Role as RoleEnum;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\DocumentApprovalDecidedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->manager = User::factory()->create();
    $this->manager->assignRole(RoleEnum::Management->value);

    $this->event = Event::factory()->create(['created_by' => $this->member->id]);
    $this->event->users()->attach([$this->member->id, $this->manager->id]);
});

test('uploading a document that requires approval creates a pending approval for its first version', function () {
    $response = $this->actingAs($this->member)->post(route('documents.store', $this->event), [
        'title' => 'Sponsorship Agreement',
        'category' => 'Contract',
        'visibility' => 'External',
        'approval_required' => true,
        'file' => UploadedFile::fake()->create('agreement.pdf', 100),
    ]);

    $response->assertRedirect();

    $document = Document::where('title', 'Sponsorship Agreement')->firstOrFail();
    expect($document->approval_required)->toBeTrue();

    $approval = $document->currentApproval();
    expect($approval)->not->toBeNull();
    expect($approval->status->value)->toBe('Pending');
    expect($approval->requested_by)->toBe($this->member->id);
    expect($document->isApprovedForSharing())->toBeFalse();
});

test('a general staff member cannot decide an approval', function () {
    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'approval_required' => true,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/file.pdf',
        'original_filename' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->member->id,
    ]);
    $document->update(['current_version_id' => $version->id]);
    $document->requestApproval($version, $this->member->id);

    $this->actingAs($this->member)->put(route('documents.approval.update', $document), [
        'status' => 'Approved',
    ])->assertForbidden();
});

test('a manager can approve a pending document and it becomes shareable', function () {
    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'approval_required' => true,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/file.pdf',
        'original_filename' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->member->id,
    ]);
    $document->update(['current_version_id' => $version->id]);
    $document->requestApproval($version, $this->member->id);

    $response = $this->actingAs($this->manager)->put(route('documents.approval.update', $document), [
        'status' => 'Approved',
        'comments' => 'Looks good.',
    ]);

    $response->assertRedirect();

    $approval = $document->currentApproval();
    expect($approval->status->value)->toBe('Approved');
    expect($approval->reviewed_by)->toBe($this->manager->id);
    expect($approval->comments)->toBe('Looks good.');
    expect($document->refresh()->isApprovedForSharing())->toBeTrue();
});

test('the requester is notified when their document is approved or rejected', function () {
    Notification::fake();

    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'approval_required' => true,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/file.pdf',
        'original_filename' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->member->id,
    ]);
    $document->update(['current_version_id' => $version->id]);
    $document->requestApproval($version, $this->member->id);

    $this->actingAs($this->manager)->put(route('documents.approval.update', $document), [
        'status' => 'Rejected',
        'comments' => 'Needs another pass.',
    ]);

    Notification::assertSentTo($this->member, DocumentApprovalDecidedNotification::class);
});

test('a manager deciding their own approval request is not notified', function () {
    Notification::fake();

    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->manager->id,
        'approval_required' => true,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/file.pdf',
        'original_filename' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->manager->id,
    ]);
    $document->update(['current_version_id' => $version->id]);
    $document->requestApproval($version, $this->manager->id);

    $this->actingAs($this->manager)->put(route('documents.approval.update', $document), [
        'status' => 'Approved',
    ]);

    Notification::assertNotSentTo($this->manager, DocumentApprovalDecidedNotification::class);
});

test('sharing a document is blocked until it is approved', function () {
    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'approval_required' => true,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/file.pdf',
        'original_filename' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->member->id,
    ]);
    $document->update(['current_version_id' => $version->id]);
    $document->requestApproval($version, $this->member->id);

    $organization = Organization::factory()->create();
    $this->event->organizations()->attach($organization->id);

    $this->actingAs($this->member)->post(route('documents.shares.store', $document), [
        'organization_id' => $organization->id,
    ])->assertForbidden();

    $this->actingAs($this->manager)->put(route('documents.approval.update', $document), [
        'status' => 'Approved',
    ]);

    $this->actingAs($this->member)->post(route('documents.shares.store', $document), [
        'organization_id' => $organization->id,
    ])->assertRedirect();
});

test('rejecting a document keeps it blocked from sharing', function () {
    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'approval_required' => true,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/file.pdf',
        'original_filename' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->member->id,
    ]);
    $document->update(['current_version_id' => $version->id]);
    $document->requestApproval($version, $this->member->id);

    $this->actingAs($this->manager)->put(route('documents.approval.update', $document), [
        'status' => 'Rejected',
        'comments' => 'Needs revisions.',
    ])->assertRedirect();

    expect($document->currentApproval()->status->value)->toBe('Rejected');
    expect($document->refresh()->isApprovedForSharing())->toBeFalse();
});

test('uploading a new version reopens approval for the new version', function () {
    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'approval_required' => true,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/file.pdf',
        'original_filename' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->member->id,
    ]);
    $document->update(['current_version_id' => $version->id]);
    $document->requestApproval($version, $this->member->id);
    $this->actingAs($this->manager)->put(route('documents.approval.update', $document), ['status' => 'Approved']);
    expect($document->refresh()->isApprovedForSharing())->toBeTrue();

    $this->actingAs($this->member)->post(route('documents.versions.store', $document), [
        'file' => UploadedFile::fake()->create('agreement-v2.pdf', 100),
    ])->assertRedirect();

    $document->refresh();
    expect(DocumentApproval::where('document_id', $document->id)->count())->toBe(2);
    expect($document->currentApproval()->status->value)->toBe('Pending');
    expect($document->isApprovedForSharing())->toBeFalse();
});

test('a document that does not require approval can be shared immediately', function () {
    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'approval_required' => false,
    ]);

    $organization = Organization::factory()->create();
    $this->event->organizations()->attach($organization->id);

    $this->actingAs($this->member)->post(route('documents.shares.store', $document), [
        'organization_id' => $organization->id,
    ])->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'documents']));
});
