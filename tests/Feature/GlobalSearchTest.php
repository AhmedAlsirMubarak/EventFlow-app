<?php

use App\Enums\Role as RoleEnum;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\Organization;
use App\Models\StakeholderRequest;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);

    $this->myEvent = Event::factory()->create(['name' => 'Riyadh Tech Summit 2026']);
    $this->myEvent->users()->attach($this->member->id);

    $this->otherEvent = Event::factory()->create(['name' => 'Dubai Finance Forum 2026']);
});

test('a query shorter than 2 characters returns no results', function () {
    $response = $this->actingAs($this->member)->get(route('search.index', ['q' => 'a']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('results.events', [])
    );
});

test('an event owner finds their own event by name', function () {
    $response = $this->actingAs($this->member)->get(route('search.index', ['q' => 'Riyadh']));

    $response->assertInertia(fn ($page) => $page
        ->has('results.events', 1)
        ->where('results.events.0.id', $this->myEvent->id)
    );
});

test('a non-member does not see another event or its documents in search results', function () {
    $document = Document::factory()->create(['event_id' => $this->otherEvent->id, 'title' => 'Dubai Sponsorship Deck']);

    $response = $this->actingAs($this->member)->get(route('search.index', ['q' => 'Dubai']));

    $response->assertInertia(fn ($page) => $page
        ->has('results.events', 0)
        ->has('results.documents', 0)
    );
});

test('an elevated role sees matching results across every event', function () {
    Document::factory()->create(['event_id' => $this->otherEvent->id, 'title' => 'Dubai Sponsorship Deck']);

    $response = $this->actingAs($this->admin)->get(route('search.index', ['q' => 'Dubai']));

    $response->assertInertia(fn ($page) => $page
        ->has('results.events', 1)
        ->has('results.documents', 1)
    );
});

test('search matches documents, tasks, and requests scoped to accessible events', function () {
    Document::factory()->create(['event_id' => $this->myEvent->id, 'title' => 'Riyadh Floor Plan']);
    Task::factory()->create(['event_id' => $this->myEvent->id, 'title' => 'Riyadh Badge Printing']);
    StakeholderRequest::factory()->create(['event_id' => $this->myEvent->id, 'title' => 'Riyadh Parking Passes']);

    $response = $this->actingAs($this->member)->get(route('search.index', ['q' => 'Riyadh']));

    $response->assertInertia(fn ($page) => $page
        ->has('results.documents', 1)
        ->has('results.tasks', 1)
        ->has('results.requests', 1)
    );
});

test('organizations and contacts are searchable by any internal user regardless of event membership', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Ministry of Trade']);
    Contact::factory()->create(['organization_id' => $organization->id, 'full_name' => 'Acme Delegate']);

    $response = $this->actingAs($this->member)->get(route('search.index', ['q' => 'Acme']));

    $response->assertInertia(fn ($page) => $page
        ->has('results.organizations', 1)
        ->has('results.contacts', 1)
    );
});

test('an external user cannot access search', function () {
    $externalUser = User::factory()->external()->create();

    $this->actingAs($externalUser)->get(route('search.index', ['q' => 'Riyadh']))
        ->assertRedirect(route('portal.dashboard'));
});
