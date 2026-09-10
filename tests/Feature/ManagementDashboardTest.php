<?php

use App\Enums\EventStatus;
use App\Enums\Role as RoleEnum;
use App\Enums\TaskStatus;
use App\Models\Document;
use App\Models\Event;
use App\Models\Organization;
use App\Models\StakeholderRequest;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);
});

test('active events indicator only counts events with Active status', function () {
    Event::factory()->create(['status' => EventStatus::Active]);
    Event::factory()->create(['status' => EventStatus::Planning]);

    $response = $this->actingAs($this->admin)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('stats', fn (Assert $page) => $page->where('active_events', 1)->etc())
    );
});

test('overdue and upcoming task indicators are computed correctly', function () {
    $event = Event::factory()->create();
    Task::factory()->create([
        'event_id' => $event->id,
        'status' => TaskStatus::Backlog,
        'due_date' => now()->subDays(2),
    ]);
    Task::factory()->create([
        'event_id' => $event->id,
        'status' => TaskStatus::Backlog,
        'due_date' => now()->addDays(3),
    ]);
    Task::factory()->create([
        'event_id' => $event->id,
        'status' => TaskStatus::Completed,
        'due_date' => now()->subDays(5),
    ]);

    $response = $this->actingAs($this->admin)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('stats', fn (Assert $page) => $page
            ->where('overdue_tasks', 1)
            ->where('upcoming_deadlines', 1)
            ->etc()
        )
    );
});

test('document indicators reflect shares, views, and pending acknowledgments', function () {
    $event = Event::factory()->create();
    $document = Document::factory()->create(['event_id' => $event->id, 'acknowledgment_required' => true]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/v1.pdf',
        'original_filename' => 'v1.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->admin->id,
    ]);
    $document->update(['current_version_id' => $version->id]);
    $document->shares()->create(['organization_id' => Organization::factory()->create()->id, 'shared_by' => $this->admin->id]);
    $document->activities()->create(['type' => 'Viewed']);

    $response = $this->actingAs($this->admin)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('stats', fn (Assert $page) => $page
            ->where('documents_shared', 1)
            ->where('documents_viewed', 1)
            ->where('documents_awaiting_acknowledgment', 1)
            ->etc()
        )
    );
});

test('pending approvals and open stakeholder requests are computed, not hardcoded', function () {
    $event = Event::factory()->create();
    $document = Document::factory()->create(['event_id' => $event->id]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/v1.pdf',
        'original_filename' => 'v1.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $this->admin->id,
    ]);
    $document->update(['current_version_id' => $version->id]);
    $document->approvals()->create([
        'document_version_id' => $version->id,
        'requested_by' => $this->admin->id,
        'status' => 'Pending',
    ]);

    StakeholderRequest::factory()->create(['event_id' => $event->id, 'status' => 'Requested']);
    StakeholderRequest::factory()->create(['event_id' => $event->id, 'status' => 'Completed']);

    $response = $this->actingAs($this->admin)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('stats', fn (Assert $page) => $page
            ->where('pending_approvals', 1)
            ->where('open_stakeholder_requests', 1)
            ->etc()
        )
    );
});

test('the event filter scopes indicators to a single event', function () {
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    Task::factory()->create(['event_id' => $eventA->id, 'due_date' => now()->subDay(), 'status' => TaskStatus::Backlog]);
    Task::factory()->create(['event_id' => $eventB->id, 'due_date' => now()->subDay(), 'status' => TaskStatus::Backlog]);

    $response = $this->actingAs($this->admin)->get(route('dashboard', ['event_id' => $eventA->id]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('stats', fn (Assert $page) => $page->where('overdue_tasks', 1)->etc())
    );
});
