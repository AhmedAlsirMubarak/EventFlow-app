<?php

use App\Enums\Role as RoleEnum;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\DocumentSharedNotification;
use App\Notifications\DocumentVersionUploadedNotification;
use App\Notifications\PortalAccessGrantedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();

    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->event = Event::factory()->create(['created_by' => $this->member->id]);
    $this->event->users()->attach($this->member->id);

    $this->document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);
    $this->document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$this->document->id.'/v1.pdf',
        'original_filename' => 'v1.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->member->id,
    ]);
    $this->document->update(['current_version_id' => $this->document->versions()->first()->id]);
});

function contactWithPortalAccess(array $attributes = []): Contact
{
    $contact = Contact::factory()->create($attributes);
    $externalUser = User::factory()->external()->create(['email' => $contact->email]);
    $contact->update(['user_id' => $externalUser->id]);

    return $contact->fresh();
}

test('sharing a document with an organization only notifies contacts who already have portal access', function () {
    $organization = Organization::factory()->create();
    $this->event->organizations()->attach($organization->id);
    $withAccess = contactWithPortalAccess(['organization_id' => $organization->id, 'email' => 'a@example.com']);
    $withoutAccess = Contact::factory()->create(['organization_id' => $organization->id, 'email' => 'b@example.com']);

    $this->actingAs($this->member)->post(route('documents.shares.store', $this->document), [
        'organization_id' => $organization->id,
    ]);

    Notification::assertSentTo($withAccess, DocumentSharedNotification::class);
    Notification::assertNotSentTo($withoutAccess, DocumentSharedNotification::class);
});

test('sharing a document with a specific contact only notifies that contact', function () {
    $organization = Organization::factory()->create();
    $this->event->organizations()->attach($organization->id);
    $targetContact = contactWithPortalAccess(['organization_id' => $organization->id, 'email' => 'target@example.com']);
    $otherContact = contactWithPortalAccess(['organization_id' => $organization->id, 'email' => 'other@example.com']);

    $this->actingAs($this->member)->post(route('documents.shares.store', $this->document), [
        'contact_id' => $targetContact->id,
    ]);

    Notification::assertSentTo($targetContact, DocumentSharedNotification::class);
    Notification::assertNotSentTo($otherContact, DocumentSharedNotification::class);
});

test('uploading a new version notifies existing share recipients who have portal access', function () {
    $organization = Organization::factory()->create();
    $contact = contactWithPortalAccess(['organization_id' => $organization->id, 'email' => 'stakeholder@example.com']);
    $this->document->shares()->create(['organization_id' => $organization->id, 'shared_by' => $this->member->id]);

    $this->actingAs($this->member)->post(route('documents.versions.store', $this->document), [
        'file' => UploadedFile::fake()->create('v2.pdf', 50, 'application/pdf'),
    ]);

    Notification::assertSentTo($contact, DocumentVersionUploadedNotification::class);
});

test('granting portal access notifies the contact with a set-password link', function () {
    $contact = Contact::factory()->create(['email' => 'newstakeholder@example.com', 'user_id' => null]);

    $this->actingAs($this->member)->post(route('contacts.portal-access', $contact));

    Notification::assertSentTo(
        $contact->fresh(),
        PortalAccessGrantedNotification::class,
    );
});
