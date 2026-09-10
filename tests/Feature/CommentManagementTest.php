<?php

use App\Enums\CommentVisibility;
use App\Enums\Role as RoleEnum;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Idea;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CommentPostedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

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

test('commenting on an idea notifies its creator', function () {
    Notification::fake();

    $creator = User::factory()->create();
    $this->event->users()->attach($creator->id);
    $idea = Idea::factory()->create(['event_id' => $this->event->id, 'created_by' => $creator->id]);

    $this->actingAs($this->member)->post(route('ideas.comments.store', $idea), [
        'body' => 'Great idea, let\'s discuss in the next meeting.',
    ]);

    Notification::assertSentTo($creator, CommentPostedNotification::class);
});

test('commenting on your own idea does not notify you', function () {
    Notification::fake();

    $idea = Idea::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $this->actingAs($this->member)->post(route('ideas.comments.store', $idea), [
        'body' => 'Following up on my own idea.',
    ]);

    Notification::assertNothingSent();
});

test('commenting on a task notifies its creator and assignee', function () {
    Notification::fake();

    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $this->event->users()->attach([$creator->id, $assignee->id]);
    $task = Task::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    $this->actingAs($this->member)->post(route('tasks.comments.store', $task), [
        'body' => 'Waiting on vendor confirmation.',
    ]);

    Notification::assertSentTo($creator, CommentPostedNotification::class);
    Notification::assertSentTo($assignee, CommentPostedNotification::class);
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
