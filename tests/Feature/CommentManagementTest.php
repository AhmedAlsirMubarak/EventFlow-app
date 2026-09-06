<?php

use App\Enums\CommentVisibility;
use App\Enums\Role as RoleEnum;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Idea;
use App\Models\Task;
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

test('a team member can comment on an idea', function () {
    $idea = Idea::factory()->create(['event_id' => $this->event->id]);

    $response = $this->actingAs($this->member)->post(route('ideas.comments.store', $idea), [
        'body' => 'Great idea, let\'s discuss in the next meeting.',
    ]);

    $response->assertRedirect();

    $comment = Comment::where('commentable_id', $idea->id)->where('commentable_type', Idea::class)->firstOrFail();
    expect($comment->user_id)->toBe($this->member->id);
    expect($comment->visibility)->toBe(CommentVisibility::Internal);
});

test('a team member can comment on a task', function () {
    $task = Task::factory()->create(['event_id' => $this->event->id]);

    $response = $this->actingAs($this->member)->post(route('tasks.comments.store', $task), [
        'body' => 'Waiting on vendor confirmation.',
    ]);

    $response->assertRedirect();
    expect(Comment::where('commentable_id', $task->id)->where('commentable_type', Task::class)->exists())->toBeTrue();
});

test('a non-member cannot comment on an idea', function () {
    $outsider = User::factory()->create();
    $idea = Idea::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($outsider)->post(route('ideas.comments.store', $idea), [
        'body' => 'Sneaky comment',
    ])->assertForbidden();
});

test('commenting requires a body', function () {
    $idea = Idea::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->member)->post(route('ideas.comments.store', $idea), [])
        ->assertSessionHasErrors('body');
});

test('a comment author can delete their own comment', function () {
    $idea = Idea::factory()->create(['event_id' => $this->event->id]);
    $comment = $idea->comments()->create([
        'user_id' => $this->member->id,
        'visibility' => CommentVisibility::Internal,
        'body' => 'Temporary note',
    ]);

    $this->actingAs($this->member)->delete(route('comments.destroy', $comment))->assertRedirect();

    expect(Comment::find($comment->id))->toBeNull();
});

test('a team member cannot delete another team member\'s comment', function () {
    $idea = Idea::factory()->create(['event_id' => $this->event->id]);
    $comment = $idea->comments()->create([
        'user_id' => $this->admin->id,
        'visibility' => CommentVisibility::Internal,
        'body' => 'Admin note',
    ]);

    $this->actingAs($this->member)->delete(route('comments.destroy', $comment))->assertForbidden();
    expect(Comment::find($comment->id))->not->toBeNull();
});

test('an elevated role can delete any comment', function () {
    $idea = Idea::factory()->create(['event_id' => $this->event->id]);
    $comment = $idea->comments()->create([
        'user_id' => $this->member->id,
        'visibility' => CommentVisibility::Internal,
        'body' => 'Note to remove',
    ]);

    $this->actingAs($this->admin)->delete(route('comments.destroy', $comment))->assertRedirect();
    expect(Comment::find($comment->id))->toBeNull();
});
