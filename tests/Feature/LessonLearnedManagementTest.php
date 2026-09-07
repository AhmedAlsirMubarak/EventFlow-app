<?php

use App\Enums\Role as RoleEnum;
use App\Models\Event;
use App\Models\LessonLearned;
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

test('a non-member cannot record a lesson learned', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->post(route('lessons.store', $this->event), [
        'category' => 'What Went Well',
        'title' => 'Great vendor coordination',
    ])->assertForbidden();
});

test('a team member can record a lesson learned', function () {
    $response = $this->actingAs($this->member)->post(route('lessons.store', $this->event), [
        'category' => 'Challenge',
        'title' => 'Registration desk understaffed',
        'description' => 'Long queues at check-in during peak hour.',
        'department' => 'Registration',
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'closure']));

    $lesson = LessonLearned::where('title', 'Registration desk understaffed')->firstOrFail();
    expect($lesson->category->value)->toBe('Challenge');
    expect($lesson->department)->toBe('Registration');
    expect($lesson->created_by)->toBe($this->member->id);
});

test('recording a lesson requires a category and title', function () {
    $this->actingAs($this->member)->post(route('lessons.store', $this->event), [])
        ->assertSessionHasErrors(['category', 'title']);
});

test('a lesson can be updated', function () {
    $lesson = LessonLearned::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $response = $this->actingAs($this->member)->put(route('lessons.update', $lesson), [
        'category' => 'Recommendation for Next Time',
        'title' => 'Book the venue earlier',
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'closure']));
    expect($lesson->refresh()->title)->toBe('Book the venue earlier');
});

test('a team member cannot delete a lesson they did not create', function () {
    $lesson = LessonLearned::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->member)->delete(route('lessons.destroy', $lesson))->assertForbidden();
});

test('an elevated role can delete any lesson', function () {
    $lesson = LessonLearned::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $this->actingAs($this->admin)->delete(route('lessons.destroy', $lesson))->assertRedirect();
    expect(LessonLearned::find($lesson->id))->toBeNull();
});
