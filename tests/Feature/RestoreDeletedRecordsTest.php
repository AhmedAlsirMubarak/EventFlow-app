<?php

use App\Enums\Role as RoleEnum;
use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\DocumentVersion;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->event = Event::factory()->create();
    $this->event->users()->attach($this->member->id);

    $this->task = Task::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);
});

test('deleting a task moves it to the bin and offers an undo link', function () {
    $response = $this->actingAs($this->member)->delete(route('tasks.destroy', $this->task));

    $this->assertSoftDeleted($this->task);
    $response->assertSessionHas('undo', route('trash.restore', ['type' => 'task', 'id' => $this->task->id]));
});

test('the undo link restores the task', function () {
    $this->actingAs($this->member)->delete(route('tasks.destroy', $this->task));

    $this->actingAs($this->member)
        ->post(route('trash.restore', ['type' => 'task', 'id' => $this->task->id]))
        ->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'tasks']));

    $this->assertNotSoftDeleted($this->task);
});

test('someone without delete permission cannot restore', function () {
    $this->task->delete();
    $outsider = User::factory()->create();
    $outsider->assignRole(RoleEnum::GeneralStaff->value);

    $this->actingAs($outsider)
        ->post(route('trash.restore', ['type' => 'task', 'id' => $this->task->id]))
        ->assertForbidden();

    $this->assertSoftDeleted($this->task);
});

test('only whitelisted record types can be restored', function () {
    $this->actingAs($this->member)
        ->post(route('trash.restore', ['type' => 'user', 'id' => $this->member->id]))
        ->assertNotFound();
});

test('restoring something that is not in the bin is a 404', function () {
    $this->actingAs($this->member)
        ->post(route('trash.restore', ['type' => 'task', 'id' => $this->task->id]))
        ->assertNotFound();
});

test('the recently deleted tab lists deleted records only when asked for', function () {
    $this->task->delete();

    $this->actingAs($this->member)
        ->get(route('events.show', $this->event))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('recentlyDeleted')
            ->reloadOnly('recentlyDeleted', fn (Assert $reload) => $reload
                ->has('recentlyDeleted', 1)
                ->where('recentlyDeleted.0.title', $this->task->title)
                ->where('recentlyDeleted.0.type', 'Task')
                ->where('recentlyDeleted.0.can_restore', true)
            )
        );
});

test('records deleted more than 30 days ago are pruned for good, recent ones are kept', function () {
    $old = Task::factory()->create(['event_id' => $this->event->id]);
    $old->delete();
    $old->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();

    $this->task->delete();

    $this->artisan('model:prune', ['--model' => [Task::class]])->assertSuccessful();

    $this->assertDatabaseMissing('tasks', ['id' => $old->id]);
    $this->assertSoftDeleted($this->task);
});

test('a deleted document keeps its files until it is pruned', function () {
    Storage::fake('local');
    Storage::disk('local')->put('documents/1/brief.pdf', 'pdf');

    $document = Document::factory()->create(['event_id' => $this->event->id]);
    DocumentVersion::factory()->create(['document_id' => $document->id, 'disk_path' => 'documents/1/brief.pdf']);

    $document->delete();
    Storage::disk('local')->assertExists('documents/1/brief.pdf');

    $document->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();
    $this->artisan('model:prune', ['--model' => [Document::class]])->assertSuccessful();

    Storage::disk('local')->assertMissing('documents/1/brief.pdf');
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
});

test("a stakeholder's portal still loads after a document shared with them is deleted", function () {
    $organization = Organization::factory()->create();
    $stakeholder = User::factory()->external()->create();
    $stakeholder->assignRole(RoleEnum::ExternalStakeholder->value);
    $contact = Contact::factory()->create(['organization_id' => $organization->id, 'user_id' => $stakeholder->id]);

    $kept = Document::factory()->create(['event_id' => $this->event->id]);
    $deleted = Document::factory()->create(['event_id' => $this->event->id]);
    DocumentShare::factory()->create(['document_id' => $kept->id, 'contact_id' => $contact->id, 'organization_id' => null]);
    DocumentShare::factory()->create(['document_id' => $deleted->id, 'contact_id' => $contact->id, 'organization_id' => null]);

    $deleted->delete();

    $this->actingAs($stakeholder)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents', 1)
            ->where('documents.0.id', $kept->id)
        );
});
