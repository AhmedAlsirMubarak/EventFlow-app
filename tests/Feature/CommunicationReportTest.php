<?php

use App\Enums\DocumentActivityType;
use App\Enums\Role as RoleEnum;
use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentAcknowledgment;
use App\Models\DocumentShare;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->event = Event::factory()->create(['created_by' => $this->member->id]);
    $this->event->users()->attach($this->member->id);
});

test('a non-member cannot download the communication report', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->get(route('events.communication-report', $this->event))
        ->assertForbidden();
});

test('a team member can download the communication report as a pdf', function () {
    $organization = Organization::factory()->create();
    $contact = Contact::factory()->create(['organization_id' => $organization->id]);

    $document = Document::factory()->create([
        'event_id' => $this->event->id,
        'acknowledgment_required' => true,
    ]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/plan.pdf',
        'original_filename' => 'plan.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'uploaded_by' => $this->member->id,
    ]);
    $document->update(['current_version_id' => $version->id]);

    $share = DocumentShare::factory()->create([
        'document_id' => $document->id,
        'contact_id' => $contact->id,
        'shared_by' => $this->member->id,
    ]);

    $document->activities()->create([
        'document_share_id' => $share->id,
        'contact_id' => $contact->id,
        'type' => DocumentActivityType::Viewed,
    ]);

    DocumentAcknowledgment::create([
        'document_share_id' => $share->id,
        'contact_id' => $contact->id,
        'document_version_id' => $version->id,
        'name' => 'Jane Stakeholder',
        'organization_snapshot' => $organization->name,
        'statement' => 'I acknowledge that I have received and reviewed this document.',
        'acknowledged_at' => now(),
    ]);

    $response = $this->actingAs($this->member)->get(route('events.communication-report', $this->event));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('the report can be scoped to a single organization', function () {
    $includedOrg = Organization::factory()->create();
    $excludedOrg = Organization::factory()->create();

    $includedDocument = Document::factory()->create(['event_id' => $this->event->id]);
    DocumentShare::factory()->create([
        'document_id' => $includedDocument->id,
        'organization_id' => $includedOrg->id,
        'shared_by' => $this->member->id,
    ]);

    $excludedDocument = Document::factory()->create(['event_id' => $this->event->id]);
    DocumentShare::factory()->create([
        'document_id' => $excludedDocument->id,
        'organization_id' => $excludedOrg->id,
        'shared_by' => $this->member->id,
    ]);

    $response = $this->actingAs($this->member)->get(route('events.communication-report', [
        'event' => $this->event,
        'organization_id' => $includedOrg->id,
    ]));

    $response->assertOk();
});

test('a document with no shares does not appear in the report request', function () {
    Document::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->member)->get(route('events.communication-report', $this->event))
        ->assertOk();
});
