<?php

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;

test('an internal user can export an organization\'s contacts as csv', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['name' => 'Acme Ministry']);
    Contact::factory()->create(['organization_id' => $organization->id, 'full_name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $response = $this->actingAs($user)->get(route('organizations.contacts.export', $organization));

    $response->assertOk();
    $content = $response->streamedContent();
    expect($content)->toContain('Jane Doe');
    expect($content)->toContain('jane@example.com');
});

test('the export only includes contacts from that organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    Contact::factory()->create(['organization_id' => $organization->id, 'full_name' => 'In Org']);
    Contact::factory()->create(['organization_id' => $otherOrganization->id, 'full_name' => 'Other Org']);

    $response = $this->actingAs($user)->get(route('organizations.contacts.export', $organization));

    $content = $response->streamedContent();
    expect($content)->toContain('In Org');
    expect($content)->not->toContain('Other Org');
});

test('an external user cannot export contacts', function () {
    $external = User::factory()->external()->create();
    $organization = Organization::factory()->create();

    $this->actingAs($external)->get(route('organizations.contacts.export', $organization))
        ->assertRedirect(route('portal.dashboard'));
});
