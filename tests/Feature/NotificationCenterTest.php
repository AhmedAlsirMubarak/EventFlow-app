<?php

use App\Enums\Role as RoleEnum;
use App\Models\Document;
use App\Models\Event;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\DocumentPendingApprovalNotification;
use App\Notifications\ReminderDueNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('a due reminder creates a real database notification for its assignee', function () {
    $assignee = User::factory()->create();
    $event = Event::factory()->create();
    Reminder::factory()->create([
        'event_id' => $event->id,
        'assigned_to' => $assignee->id,
        'remind_at' => now()->subMinute(),
    ]);

    $this->artisan('app:send-due-reminders');

    expect($assignee->notifications()->count())->toBe(1);
    $notification = $assignee->notifications()->firstOrFail();
    expect($notification->type)->toBe(ReminderDueNotification::class);
    expect($notification->data['title'])->toContain('Reminder due');
    expect($notification->read_at)->toBeNull();
});

test('requesting approval on a document notifies every elevated reviewer', function () {
    Storage::fake('local');
    Notification::fake();

    $member = User::factory()->create();
    $member->assignRole(RoleEnum::GeneralStaff->value);

    $management = User::factory()->create();
    $management->assignRole(RoleEnum::Management->value);

    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::SuperAdministrator->value);

    $event = Event::factory()->create(['created_by' => $member->id]);
    $event->users()->attach($member->id);

    $this->actingAs($member)->post(route('documents.store', $event), [
        'title' => 'Sponsorship Agreement',
        'category' => 'Contract',
        'visibility' => 'External',
        'approval_required' => true,
        'file' => UploadedFile::fake()->create('agreement.pdf', 100),
    ]);

    Notification::assertSentTo($management, DocumentPendingApprovalNotification::class);
    Notification::assertSentTo($admin, DocumentPendingApprovalNotification::class);
    Notification::assertNotSentTo($member, DocumentPendingApprovalNotification::class);
});

test('requesting approval writes a real database notification with a link to the documents tab', function () {
    Storage::fake('local');

    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::SuperAdministrator->value);

    $event = Event::factory()->create();
    $document = Document::factory()->create(['event_id' => $event->id, 'approval_required' => true]);
    $version = $document->versions()->create([
        'version_number' => 1,
        'disk_path' => 'documents/'.$document->id.'/plan.pdf',
        'original_filename' => 'plan.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => $admin->id,
    ]);

    $document->requestApproval($version, $admin->id);

    $notification = $admin->notifications()->firstOrFail();
    expect($notification->data['url'])->toContain((string) $event->id);
});

test('the unread notifications count is shared on every inertia page', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    Reminder::factory()->create([
        'event_id' => $event->id,
        'assigned_to' => $user->id,
        'remind_at' => now()->subMinute(),
    ]);
    $this->artisan('app:send-due-reminders');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page->where('unreadNotificationsCount', 1)->etc());
});

test('a user can view their notifications', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    Reminder::factory()->create([
        'event_id' => $event->id,
        'assigned_to' => $user->id,
        'remind_at' => now()->subMinute(),
    ]);
    $this->artisan('app:send-due-reminders');

    $response = $this->actingAs($user)->get(route('notifications.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Notifications/Index')
        ->has('notifications.data', 1)
    );
});

test('reading a notification marks it read and redirects to its url', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    Reminder::factory()->create([
        'event_id' => $event->id,
        'assigned_to' => $user->id,
        'remind_at' => now()->subMinute(),
    ]);
    $this->artisan('app:send-due-reminders');
    $notification = $user->notifications()->firstOrFail();

    $response = $this->actingAs($user)->post(route('notifications.read', $notification->id));

    $response->assertRedirect($notification->data['url']);
    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a user cannot mark another users notification as read', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $event = Event::factory()->create();
    Reminder::factory()->create([
        'event_id' => $event->id,
        'assigned_to' => $owner->id,
        'remind_at' => now()->subMinute(),
    ]);
    $this->artisan('app:send-due-reminders');
    $notification = $owner->notifications()->firstOrFail();

    $this->actingAs($intruder)->post(route('notifications.read', $notification->id))
        ->assertForbidden();
});

test('mark all read clears every unread notification for the user', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    Reminder::factory()->count(3)->create([
        'event_id' => $event->id,
        'assigned_to' => $user->id,
        'remind_at' => now()->subMinute(),
    ]);
    $this->artisan('app:send-due-reminders');

    $response = $this->actingAs($user)->post(route('notifications.read-all'));

    $response->assertRedirect();
    expect($user->unreadNotifications()->count())->toBe(0);
});
