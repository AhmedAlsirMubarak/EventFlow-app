<?php

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->member = User::factory()->create();
    $this->organization = Organization::factory()->create();
});

test('an internal user can download the csv template with the correct headers', function () {
    $response = $this->actingAs($this->member)->get(route('contacts.import-template'));

    $response->assertOk();
    $content = $response->streamedContent();
    expect($content)->toContain('full_name,job_title,department,email,mobile,preferred_channel');
});

test('a non-internal user cannot download the csv template', function () {
    $external = User::factory()->external()->create();

    $this->actingAs($external)->get(route('contacts.import-template'))
        ->assertRedirect(route('portal.dashboard'));
});

test('a non-internal user cannot import contacts', function () {
    $external = User::factory()->external()->create();
    $csv = UploadedFile::fake()->createWithContent('contacts.csv', "full_name,email\nJane Doe,jane@example.com\n");

    $this->actingAs($external)->post(route('organizations.contacts.import', $this->organization), [
        'file' => $csv,
    ])->assertRedirect(route('portal.dashboard'));

    expect(Contact::count())->toBe(0);
});

test('a valid csv imports every contact', function () {
    $content = "full_name,email,job_title,department,mobile,preferred_channel\n"
        ."Jane Doe,jane@example.com,Director,Operations,0500000000,Email\n"
        ."John Smith,john@example.com,Manager,Finance,0511111111,Phone\n";
    $csv = UploadedFile::fake()->createWithContent('contacts.csv', $content);

    $response = $this->actingAs($this->member)->post(route('organizations.contacts.import', $this->organization), [
        'file' => $csv,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'Imported 2 contact(s).');

    expect(Contact::where('organization_id', $this->organization->id)->count())->toBe(2);
    $jane = Contact::where('email', 'jane@example.com')->firstOrFail();
    expect($jane->full_name)->toBe('Jane Doe');
    expect($jane->department)->toBe('Operations');
    expect($jane->preferred_channel->value)->toBe('Email');
});

test('valid rows are imported and invalid rows are reported without failing the whole file', function () {
    $content = "full_name,email\n"
        ."Jane Doe,jane@example.com\n"
        .",missing-name@example.com\n"
        ."John Smith,not-an-email\n";
    $csv = UploadedFile::fake()->createWithContent('contacts.csv', $content);

    $response = $this->actingAs($this->member)->post(route('organizations.contacts.import', $this->organization), [
        'file' => $csv,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'Imported 1 contact(s). 2 row(s) skipped — see details below.');
    $response->assertSessionHas('importErrors', fn ($errors) => count($errors) === 2);

    expect(Contact::where('organization_id', $this->organization->id)->count())->toBe(1);
});

test('a csv without a full_name column is rejected', function () {
    $csv = UploadedFile::fake()->createWithContent('contacts.csv', "email\njane@example.com\n");

    $response = $this->actingAs($this->member)->post(route('organizations.contacts.import', $this->organization), [
        'file' => $csv,
    ]);

    $response->assertSessionHas('status', 'The CSV must include a "full_name" column.');
    expect(Contact::count())->toBe(0);
});

test('column order does not matter', function () {
    $content = "email,full_name\njane@example.com,Jane Doe\n";
    $csv = UploadedFile::fake()->createWithContent('contacts.csv', $content);

    $this->actingAs($this->member)->post(route('organizations.contacts.import', $this->organization), [
        'file' => $csv,
    ]);

    expect(Contact::where('full_name', 'Jane Doe')->exists())->toBeTrue();
});
