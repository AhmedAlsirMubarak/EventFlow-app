<?php

use App\Enums\IdeaStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Event;
use App\Models\Idea;
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

test('a non-member cannot submit an idea for an event', function () {
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->post(route('ideas.store', $this->event), [
        'title' => 'Add a VR booth',
        'status' => IdeaStatus::New->value,
    ]);

    $response->assertForbidden();
});

test('a team member can submit an idea that defaults to New', function () {
    $response = $this->actingAs($this->member)->post(route('ideas.store', $this->event), [
        'title' => 'Add a VR booth',
        'category' => 'Technology',
        'status' => IdeaStatus::New->value,
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'ideas']));

    $idea = Idea::where('title', 'Add a VR booth')->firstOrFail();
    expect($idea->status)->toBe(IdeaStatus::New);
    expect($idea->created_by)->toBe($this->member->id);
});

test('any team member can move an idea through its status workflow', function () {
    $idea = Idea::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->admin->id,
        'status' => IdeaStatus::New,
    ]);

    $response = $this->actingAs($this->member)->put(route('ideas.update', $idea), [
        'title' => $idea->title,
        'status' => IdeaStatus::UnderDiscussion->value,
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'ideas']));
    expect($idea->refresh()->status)->toBe(IdeaStatus::UnderDiscussion);
});

test('a team member cannot delete an idea they did not create', function () {
    $idea = Idea::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->member)->delete(route('ideas.destroy', $idea))->assertForbidden();
});

test('the creator can delete their own idea', function () {
    $idea = Idea::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);

    $this->actingAs($this->member)->delete(route('ideas.destroy', $idea))
        ->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'ideas']));

    expect(Idea::find($idea->id))->toBeNull();
});

test('an elevated role can delete any idea', function () {
    $idea = Idea::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);

    $this->actingAs($this->admin)->delete(route('ideas.destroy', $idea))->assertRedirect();
    expect(Idea::find($idea->id))->toBeNull();
});
