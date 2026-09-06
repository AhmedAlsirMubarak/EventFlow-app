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
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->seed(RoleSeeder::class);

    $this->organization = Organization::factory()->create();
    $this->contact = Contact::factory()->create(['organization_id' => $this->organization->id]);

    $this->externalUser = User::factory()->external()->create();
    $this->externalUser->assignRole(RoleEnum::ExternalStakeholder->value);
    $this->contact->update(['user_id' => $this->externalUser->id]);

    $event = Event::factory()->create();
    $this->document = Document::factory()->create([
        'event_id' => $event->id,
        'acknowledgment_required' => true,
    ]);
    $this->document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$this->document->id.'/plan.pdf',
        'original_filename' => 'plan.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'uploaded_by' => $event->created_by,
    ]);
    $this->document->update(['current_version_id' => $this->document->versions()->first()->id]);
    Storage::disk('local')->put($this->document->currentVersion->disk_path, 'fake contents');

    $this->share = DocumentShare::factory()->create([
        'document_id' => $this->document->id,
        'organization_id' => $this->organization->id,
        'shared_by' => $event->created_by,
    ]);
});

test('internal users are redirected away from the portal', function () {
    $internal = User::factory()->create();

    $this->actingAs($internal)->get(route('portal.dashboard'))->assertRedirect(route('dashboard'));
});

test('an external user sees documents shared with their organization', function () {
    $response = $this->actingAs($this->externalUser)->get(route('portal.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Dashboard')
        ->has('documents', 1)
        ->where('documents.0.id', $this->document->id)
    );
});

test('viewing a shared document logs a Viewed activity', function () {
    $this->actingAs($this->externalUser)->get(route('portal.documents.show', $this->document))->assertOk();

    expect($this->document->activities()
        ->where('contact_id', $this->contact->id)
        ->where('type', DocumentActivityType::Viewed)
        ->exists())->toBeTrue();
});

test('an external user cannot view a document not shared with them', function () {
    $otherDocument = Document::factory()->create();

    $this->actingAs($this->externalUser)->get(route('portal.documents.show', $otherDocument))->assertForbidden();
});

test('downloading a shared document logs a Downloaded activity once acknowledged', function () {
    DocumentAcknowledgment::create([
        'document_share_id' => $this->share->id,
        'contact_id' => $this->contact->id,
        'document_version_id' => $this->document->current_version_id,
        'name' => 'Jane Stakeholder',
        'statement' => 'I acknowledge that I have received and reviewed this document.',
        'acknowledged_at' => now(),
    ]);

    $response = $this->actingAs($this->externalUser)->get(
        route('portal.document-versions.download', $this->document->current_version_id),
    );

    $response->assertOk();
    expect($this->document->activities()
        ->where('contact_id', $this->contact->id)
        ->where('type', DocumentActivityType::Downloaded)
        ->exists())->toBeTrue();
});

test('downloading a document requiring acknowledgment is blocked until acknowledged', function () {
    $response = $this->actingAs($this->externalUser)->get(
        route('portal.document-versions.download', $this->document->current_version_id),
    );

    $response->assertForbidden();
    expect($this->document->activities()
        ->where('type', DocumentActivityType::Downloaded)
        ->exists())->toBeFalse();
});

test('the document page reports canDownload as false until acknowledged', function () {
    $response = $this->actingAs($this->externalUser)->get(route('portal.documents.show', $this->document));

    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Show')
        ->where('canDownload', false)
        ->etc()
    );
});

test('a contact from a different organization cannot download the document', function () {
    $otherOrg = Organization::factory()->create();
    $otherContact = Contact::factory()->create(['organization_id' => $otherOrg->id]);
    $otherUser = User::factory()->external()->create();
    $otherContact->update(['user_id' => $otherUser->id]);

    $this->actingAs($otherUser)->get(
        route('portal.document-versions.download', $this->document->current_version_id),
    )->assertForbidden();
});

test('an external user can acknowledge a document requiring acknowledgment', function () {
    $response = $this->actingAs($this->externalUser)->post(
        route('portal.documents.acknowledge', $this->document),
        ['name' => 'Jane Stakeholder'],
    );

    $response->assertRedirect();

    $acknowledgment = DocumentAcknowledgment::where('document_share_id', $this->share->id)->firstOrFail();
    expect($acknowledgment->name)->toBe('Jane Stakeholder');
    expect($acknowledgment->contact_id)->toBe($this->contact->id);

    expect($this->document->activities()
        ->where('type', DocumentActivityType::Acknowledged)
        ->exists())->toBeTrue();
});

test('acknowledgment requires a name', function () {
    $response = $this->actingAs($this->externalUser)->post(
        route('portal.documents.acknowledge', $this->document),
        [],
    );

    $response->assertSessionHasErrors('name');
});
