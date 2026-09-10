<?php

use App\Enums\Role as RoleEnum;
use App\Models\AbstractCall;
use App\Models\AbstractSubmission;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\User;
use App\Notifications\AbstractAwaitingReviewNotification;
use App\Notifications\AbstractReviewSubmittedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->reviewer = User::factory()->create();
    $this->reviewer->assignRole(RoleEnum::GeneralStaff->value);

    $this->chair = User::factory()->create();
    $this->chair->assignRole(RoleEnum::GeneralStaff->value);

    $this->event = Event::factory()->create();
    $this->event->users()->attach([$this->member->id, $this->reviewer->id, $this->chair->id]);
    CommitteeMember::factory()->create(['event_id' => $this->event->id, 'user_id' => $this->reviewer->id, 'committee_role' => 'Reviewer']);
    CommitteeMember::factory()->create(['event_id' => $this->event->id, 'user_id' => $this->chair->id, 'committee_role' => 'Chair']);

    $this->call = AbstractCall::factory()->create(['event_id' => $this->event->id]);
    $this->submission = AbstractSubmission::factory()->create(['abstract_call_id' => $this->call->id]);
});

test('a non-committee event member cannot review a submission', function () {
    $this->actingAs($this->member)->post(route('abstract-submissions.reviews.store', $this->submission), [
        'score' => 4,
        'recommendation' => 'Accept',
    ])->assertForbidden();
});

test('a committee member can score a submission and it moves to under review', function () {
    $response = $this->actingAs($this->reviewer)->post(route('abstract-submissions.reviews.store', $this->submission), [
        'score' => 4,
        'recommendation' => 'Accept',
        'comments' => 'Strong proposal.',
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'abstracts']));

    $review = $this->submission->reviews()->where('user_id', $this->reviewer->id)->firstOrFail();
    expect($review->score)->toBe(4);
    expect($review->recommendation->value)->toBe('Accept');
    expect($this->submission->refresh()->status->value)->toBe('Under Review');
});

test('submitting a review notifies the chair', function () {
    Notification::fake();

    $this->actingAs($this->reviewer)->post(route('abstract-submissions.reviews.store', $this->submission), [
        'score' => 4,
        'recommendation' => 'Accept',
    ]);

    Notification::assertSentTo(
        $this->chair,
        AbstractReviewSubmittedNotification::class,
        fn ($notification, $channels) => in_array('mail', $channels) && in_array('database', $channels)
    );
});

test('the chair reviewing a submission does not notify themselves', function () {
    Notification::fake();

    $this->actingAs($this->chair)->post(route('abstract-submissions.reviews.store', $this->submission), [
        'score' => 5,
        'recommendation' => 'Accept',
    ]);

    Notification::assertNotSentTo($this->chair, AbstractReviewSubmittedNotification::class);
});

test('reviewing twice updates the same row instead of duplicating', function () {
    $this->actingAs($this->reviewer)->post(route('abstract-submissions.reviews.store', $this->submission), [
        'score' => 2,
        'recommendation' => 'Reject',
    ]);

    $this->actingAs($this->reviewer)->post(route('abstract-submissions.reviews.store', $this->submission), [
        'score' => 5,
        'recommendation' => 'Accept',
        'comments' => 'Changed my mind after re-reading.',
    ]);

    expect($this->submission->reviews()->where('user_id', $this->reviewer->id)->count())->toBe(1);
    $review = $this->submission->reviews()->where('user_id', $this->reviewer->id)->firstOrFail();
    expect($review->score)->toBe(5);
    expect($review->recommendation->value)->toBe('Accept');
});

test('a score must be between 1 and 5', function () {
    $this->actingAs($this->reviewer)->post(route('abstract-submissions.reviews.store', $this->submission), [
        'score' => 9,
        'recommendation' => 'Accept',
    ])->assertSessionHasErrors('score');
});

test('committee members are notified in-app and by email when a submission arrives', function () {
    Notification::fake();

    $this->post(route('public.abstracts.store', $this->call->public_token), [
        'title' => 'A New Talk',
        'abstract_body' => 'Body',
        'speaker_name' => 'Someone',
        'speaker_email' => 'someone@example.com',
    ]);

    Notification::assertSentTo(
        $this->reviewer,
        AbstractAwaitingReviewNotification::class,
        fn ($notification, $channels) => in_array('mail', $channels) && in_array('database', $channels)
    );
});

test('a committee member can download a submission attachment', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('slides.pdf', 100, 'application/pdf');
    $path = $file->store('abstract-submissions/'.$this->submission->id, 'local');
    $this->submission->update(['disk_path' => $path, 'original_filename' => 'slides.pdf']);

    $this->actingAs($this->reviewer)
        ->get(route('abstract-submissions.download', $this->submission))
        ->assertOk();
});

test('a non-committee event member cannot download a submission attachment', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('slides.pdf', 100, 'application/pdf');
    $path = $file->store('abstract-submissions/'.$this->submission->id, 'local');
    $this->submission->update(['disk_path' => $path, 'original_filename' => 'slides.pdf']);

    $this->actingAs($this->member)
        ->get(route('abstract-submissions.download', $this->submission))
        ->assertForbidden();
});

test('downloading a submission with no attachment 404s', function () {
    $this->actingAs($this->reviewer)
        ->get(route('abstract-submissions.download', $this->submission))
        ->assertNotFound();
});
