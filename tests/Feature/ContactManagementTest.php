<?php

use App\Enums\PreferredChannel;
use App\Enums\Role as RoleEnum;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->staff = User::factory()->create();
    $this->staff->assignRole(RoleEnum::GeneralStaff->value);

    $this->organization = Organization::factory()->create();
});

test('external users cannot manage contacts', function () {
    $external = User::factory()->external()->create();

    $response = $this->actingAs($external)->post(
        route('organizations.contacts.store', $this->organization),
        ['full_name' => 'Jane Doe'],
    );

    $response->assertRedirect(route('portal.dashboard'));
});

test('an internal user can add a contact to an organization', function () {
    $response = $this->actingAs($this->staff)->post(
        route('organizations.contacts.store', $this->organization),
        [
            'full_name' => 'Jane Doe',
            'job_title' => 'Director',
            'email' => 'jane@example.com',
            'preferred_channel' => PreferredChannel::WhatsApp->value,
        ],
    );

    $response->assertRedirect(route('organizations.show', $this->organization));

    $contact = Contact::where('email', 'jane@example.com')->firstOrFail();
    expect($contact->organization_id)->toBe($this->organization->id);
    expect($contact->preferred_channel)->toBe(PreferredChannel::WhatsApp);
});

test('a contact is correctly scoped to its own organization', function () {
    $otherOrganization = Organization::factory()->create();
    $contact = Contact::factory()->create(['organization_id' => $this->organization->id]);

    expect($this->organization->contacts()->find($contact->id))->not->toBeNull();
    expect($otherOrganization->contacts()->find($contact->id))->toBeNull();
});

test('an internal user can update a contact', function () {
    $contact = Contact::factory()->create(['organization_id' => $this->organization->id]);

    $response = $this->actingAs($this->staff)->put(route('contacts.update', $contact), [
        'full_name' => 'Updated Name',
    ]);

    $response->assertRedirect(route('organizations.show', $this->organization));
    expect($contact->refresh()->full_name)->toBe('Updated Name');
});

test('an internal user can remove a contact', function () {
    $contact = Contact::factory()->create(['organization_id' => $this->organization->id]);

    $response = $this->actingAs($this->staff)->delete(route('contacts.destroy', $contact));

    $response->assertRedirect(route('organizations.show', $this->organization));
    expect(Contact::find($contact->id))->toBeNull();
});

test('adding a contact requires a full name', function () {
    $response = $this->actingAs($this->staff)->post(
        route('organizations.contacts.store', $this->organization),
        ['job_title' => 'Director'],
    );

    $response->assertSessionHasErrors('full_name');
});
