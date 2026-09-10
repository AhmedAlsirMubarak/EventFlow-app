<?php

use App\Enums\Role;
use App\Models\Decision;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Event;
use App\Models\FloorPlan;
use App\Models\Idea;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

// Every one of these FKs was cascadeOnDelete: deleting the user who created a record
// used to silently delete the record too. They're nullOnDelete now — the record
// survives with a null author. See the 2026_09_10_110745 migration.
test('deleting a user preserves the events, tasks, documents, and other records they created', function () {
    $author = User::factory()->create();

    $event = Event::factory()->create(['created_by' => $author->id]);
    $idea = Idea::factory()->create(['event_id' => $event->id, 'created_by' => $author->id]);
    $task = Task::factory()->create(['event_id' => $event->id, 'created_by' => $author->id]);
    $document = Document::factory()->create(['event_id' => $event->id, 'created_by' => $author->id]);
    $version = DocumentVersion::factory()->create(['document_id' => $document->id, 'uploaded_by' => $author->id]);
    $meeting = Meeting::factory()->create(['event_id' => $event->id, 'created_by' => $author->id]);
    $decision = Decision::factory()->create(['event_id' => $event->id, 'created_by' => $author->id]);
    $floorPlan = FloorPlan::factory()->create(['event_id' => $event->id, 'created_by' => $author->id]);

    $author->delete();

    expect($event->fresh())->not->toBeNull();
    expect($event->fresh()->created_by)->toBeNull();

    expect($idea->fresh()->created_by)->toBeNull();
    expect($task->fresh()->created_by)->toBeNull();
    expect($document->fresh()->created_by)->toBeNull();
    expect($version->fresh()->uploaded_by)->toBeNull();
    expect($meeting->fresh()->created_by)->toBeNull();
    expect($decision->fresh()->created_by)->toBeNull();
    expect($floorPlan->fresh()->created_by)->toBeNull();
});

test('the event page still renders after its creator is deleted', function () {
    $author = User::factory()->create();
    $viewer = User::factory()->create();

    $event = Event::factory()->create(['created_by' => $author->id]);
    $event->users()->attach($viewer->id);

    $author->delete();

    $this->actingAs($viewer)->get(route('events.show', $event))->assertOk();
});

test('an administrator removing a user does not delete that user\'s events', function () {
    Storage::fake('public');
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(Role::SuperAdministrator->value);
    $this->withSession(['auth.password_confirmed_at' => time()]);

    $author = User::factory()->create();
    $event = Event::factory()->create(['created_by' => $author->id]);

    $this->actingAs($admin)->delete(route('admin.users.destroy', $author))->assertRedirect();

    expect($event->fresh())->not->toBeNull();
    expect($event->fresh()->created_by)->toBeNull();
});
