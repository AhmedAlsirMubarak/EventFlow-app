<?php

use App\Enums\DocumentActivityType;
use App\Enums\Role as RoleEnum;
use App\Models\Contact;
use App\Models\Document;
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

    $this->document = Document::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);
});

test('a team member can share a document with an entire organization', function () {
    $organization = Organization::factory()->create();

    $response = $this->actingAs($this->member)->post(route('documents.shares.store', $this->document), [
        'organization_id' => $organization->id,
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'documents']));

    $share = DocumentShare::where('document_id', $this->document->id)->firstOrFail();
    expect($share->organization_id)->toBe($organization->id);
    expect($share->contact_id)->toBeNull();

    expect($this->document->activities()->where('type', DocumentActivityType::Shared)->exists())->toBeTrue();
});

test('a team member can share a document with a specific contact', function () {
    $contact = Contact::factory()->create();

    $this->actingAs($this->member)->post(route('documents.shares.store', $this->document), [
        'contact_id' => $contact->id,
    ])->assertRedirect();

    $share = DocumentShare::where('document_id', $this->document->id)->firstOrFail();
    expect($share->contact_id)->toBe($contact->id);
    expect($share->organization_id)->toBeNull();
});

test('sharing requires either an organization or a contact', function () {
    $response = $this->actingAs($this->member)->post(route('documents.shares.store', $this->document), []);

    $response->assertSessionHasErrors('organization_id');
});

test('a non-member cannot share a document', function () {
    $outsider = User::factory()->create();
    $organization = Organization::factory()->create();

    $this->actingAs($outsider)->post(route('documents.shares.store', $this->document), [
        'organization_id' => $organization->id,
    ])->assertForbidden();
});

test('a share can be revoked', function () {
    $share = DocumentShare::factory()->create([
        'document_id' => $this->document->id,
        'organization_id' => Organization::factory(),
        'shared_by' => $this->member->id,
    ]);

    $this->actingAs($this->member)->delete(route('document-shares.destroy', $share))->assertRedirect();

    expect(DocumentShare::find($share->id))->toBeNull();
});

test('granting portal access creates an external account linked to the contact', function () {
    $contact = Contact::factory()->create(['email' => 'stakeholder@example.com', 'user_id' => null]);

    $response = $this->actingAs($this->member)->post(route('contacts.portal-access', $contact));

    $response->assertRedirect();
    $contact->refresh();
    expect($contact->user_id)->not->toBeNull();
    expect($contact->user->isExternal())->toBeTrue();
    expect($contact->user->hasRole(RoleEnum::ExternalStakeholder->value))->toBeTrue();
});

test('granting portal access fails gracefully without an email', function () {
    $contact = Contact::factory()->create(['email' => null, 'user_id' => null]);

    $this->actingAs($this->member)->post(route('contacts.portal-access', $contact))->assertRedirect();

    expect($contact->refresh()->user_id)->toBeNull();
});

test('granting portal access twice does not create a second account', function () {
    $contact = Contact::factory()->create(['email' => 'stakeholder@example.com', 'user_id' => null]);
    $this->actingAs($this->member)->post(route('contacts.portal-access', $contact));
    $contact->refresh();
    $firstUserId = $contact->user_id;

    $this->actingAs($this->member)->post(route('contacts.portal-access', $contact));

    expect($contact->refresh()->user_id)->toBe($firstUserId);
});

test('a contact with existing portal access can have their access link resent', function () {
    $contact = Contact::factory()->create(['email' => 'stakeholder@example.com', 'user_id' => null]);
    $this->actingAs($this->member)->post(route('contacts.portal-access', $contact));
    $contact->refresh();

    $response = $this->actingAs($this->member)->post(route('contacts.portal-access.resend', $contact));

    $response->assertRedirect();
    $response->assertSessionHas('resetLink');
    $response->assertSessionHas('status', 'Portal access link resent.');
});

test('resending portal access without an existing account fails gracefully', function () {
    $contact = Contact::factory()->create(['user_id' => null]);

    $response = $this->actingAs($this->member)->post(route('contacts.portal-access.resend', $contact));

    $response->assertRedirect();
    $response->assertSessionMissing('resetLink');
});
