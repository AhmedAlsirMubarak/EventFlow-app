<?php

use App\Models\Event;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderDueNotification;
use Illuminate\Support\Facades\Notification;

test('due reminders notify their assignee and are marked notified', function () {
    Notification::fake();

    $assignee = User::factory()->create();
    $event = Event::factory()->create();
    $reminder = Reminder::factory()->create([
        'event_id' => $event->id,
        'assigned_to' => $assignee->id,
        'remind_at' => now()->subMinute(),
    ]);

    $this->artisan('app:send-due-reminders')->assertExitCode(0);

    Notification::assertSentTo($assignee, ReminderDueNotification::class);
    expect($reminder->refresh()->notified_at)->not->toBeNull();
});

test('a reminder without an assignee falls back to the creator', function () {
    Notification::fake();

    $creator = User::factory()->create();
    $event = Event::factory()->create();
    Reminder::factory()->create([
        'event_id' => $event->id,
        'created_by' => $creator->id,
        'remind_at' => now()->subMinute(),
    ]);

    $this->artisan('app:send-due-reminders');

    Notification::assertSentTo($creator, ReminderDueNotification::class);
});

test('a future reminder is not notified yet', function () {
    Notification::fake();

    $assignee = User::factory()->create();
    $event = Event::factory()->create();
    $reminder = Reminder::factory()->create([
        'event_id' => $event->id,
        'assigned_to' => $assignee->id,
        'remind_at' => now()->addDay(),
    ]);

    $this->artisan('app:send-due-reminders');

    Notification::assertNothingSent();
    expect($reminder->refresh()->notified_at)->toBeNull();
});

test('an already-notified reminder is not notified again', function () {
    Notification::fake();

    $assignee = User::factory()->create();
    $event = Event::factory()->create();
    Reminder::factory()->create([
        'event_id' => $event->id,
        'assigned_to' => $assignee->id,
        'remind_at' => now()->subDay(),
        'notified_at' => now()->subHour(),
    ]);

    $this->artisan('app:send-due-reminders');

    Notification::assertNothingSent();
});

test('a completed reminder is not notified even if overdue', function () {
    Notification::fake();

    $assignee = User::factory()->create();
    $event = Event::factory()->create();
    Reminder::factory()->create([
        'event_id' => $event->id,
        'assigned_to' => $assignee->id,
        'remind_at' => now()->subDay(),
        'status' => 'Completed',
        'completed_at' => now(),
    ]);

    $this->artisan('app:send-due-reminders');

    Notification::assertNothingSent();
});
