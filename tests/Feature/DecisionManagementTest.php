<?php

use App\Enums\Role as RoleEnum;
use App\Models\Decision;
use App\Models\Document;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);

    $this->event = Event::factory()->create(['created_by' => $this->member->id]);
    $this->event->users()->attach($this->member->id);
});

test('a non-member cannot record a decision', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->post(route('decisions.store', $this->event), [
        'title' => 'Approve floor plan v4',
        'decision_date' => now()->format('Y-m-d'),
    ])->assertForbidden();
});

test('a team member can record a decision with a generated reference', function () {
    $organization = Organization::factory()->create();
    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);
    $document = Document::factory()->create(['event_id' => $this->event->id]);
    $supportingDoc = Document::factory()->create(['event_id' => $this->event->id]);

    $response = $this->actingAs($this->member)->post(route('decisions.store', $this->event), [
        'title' => 'Approve floor plan v4',
        'decision_date' => now()->format('Y-m-d'),
        'decided_by' => $this->admin->id,
        'organization_id' => $organization->id,
        'meeting_id' => $meeting->id,
        'document_id' => $document->id,
        'reason' => 'Client requested VIP seating changes.',
        'supporting_document_ids' => [$supportingDoc->id],
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'decisions']));

    $decision = Decision::where('title', 'Approve floor plan v4')->firstOrFail();
    expect($decision->reference)->toStartWith('DEC-');
    expect($decision->decided_by)->toBe($this->admin->id);
    expect($decision->meeting_id)->toBe($meeting->id);
    expect($decision->document_id)->toBe($document->id);
    expect($decision->supportingDocuments()->where('documents.id', $supportingDoc->id)->exists())->toBeTrue();
});

test('recording a decision requires a title and decision date', function () {
    $this->actingAs($this->member)->post(route('decisions.store', $this->event), [])
        ->assertSessionHasErrors(['title', 'decision_date']);
});

test('a decision can be updated', function () {
    $decision = Decision::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $response = $this->actingAs($this->member)->put(route('decisions.update', $decision), [
        'title' => 'Updated decision title',
        'decision_date' => $decision->decision_date,
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'decisions']));
    expect($decision->refresh()->title)->toBe('Updated decision title');
});

test('a team member cannot delete a decision they did not create', function () {
    $decision = Decision::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->member)->delete(route('decisions.destroy', $decision))->assertForbidden();
});

test('an elevated role can delete any decision', function () {
    $decision = Decision::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $this->actingAs($this->admin)->delete(route('decisions.destroy', $decision))->assertRedirect();
    expect(Decision::find($decision->id))->toBeNull();
});
