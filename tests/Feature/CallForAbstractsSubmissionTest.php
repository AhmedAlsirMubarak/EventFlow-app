<?php

use App\Models\AbstractCall;
use App\Models\AbstractSubmission;
use App\Models\AbstractTrack;
use App\Notifications\AbstractSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->call = AbstractCall::factory()->create(['is_open' => true]);
});

test('the public call page is reachable without authentication', function () {
    $this->get(route('public.abstracts.show', $this->call->public_token))->assertOk();
});

test('an unknown token 404s instead of leaking anything', function () {
    $this->get('/call/not-a-real-token')->assertNotFound();
});

test('a valid submission is created and the submitter is emailed a status link', function () {
    Notification::fake();

    $track = AbstractTrack::factory()->create(['abstract_call_id' => $this->call->id]);

    $response = $this->post(route('public.abstracts.store', $this->call->public_token), [
        'title' => 'Scaling Event Operations',
        'abstract_track_id' => $track->id,
        'abstract_body' => 'A talk about scaling event operations across regions.',
        'speaker_name' => 'Jane Speaker',
        'speaker_email' => 'jane@example.com',
        'speaker_organization' => 'Acme Conferences',
    ]);

    $submission = AbstractSubmission::where('title', 'Scaling Event Operations')->firstOrFail();
    expect($submission->reference)->toStartWith('ABS-');
    expect($submission->status->value)->toBe('Submitted');
    expect($submission->abstract_track_id)->toBe($track->id);
    expect($submission->public_token)->not->toBeEmpty();

    $response->assertRedirect(route('public.abstracts.status', $submission->public_token));

    Notification::assertSentOnDemand(
        AbstractSubmittedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'jane@example.com'
    );
});

test('submitting requires the core fields', function () {
    $this->post(route('public.abstracts.store', $this->call->public_token), [])
        ->assertSessionHasErrors(['title', 'abstract_body', 'speaker_name', 'speaker_email']);
});

test('a manually closed call rejects submissions', function () {
    $this->call->update(['is_open' => false]);

    $this->post(route('public.abstracts.store', $this->call->public_token), [
        'title' => 'Late Talk',
        'abstract_body' => 'Body',
        'speaker_name' => 'Someone',
        'speaker_email' => 'someone@example.com',
    ])->assertForbidden();

    expect(AbstractSubmission::count())->toBe(0);
});

test('a call past its closing date rejects submissions', function () {
    $this->call->update(['closes_at' => now()->subDay()]);

    $this->post(route('public.abstracts.store', $this->call->public_token), [
        'title' => 'Late Talk',
        'abstract_body' => 'Body',
        'speaker_name' => 'Someone',
        'speaker_email' => 'someone@example.com',
    ])->assertForbidden();

    expect(AbstractSubmission::count())->toBe(0);
});

test('a submission with an attachment stores the file privately', function () {
    $file = UploadedFile::fake()->create('slides.pdf', 200, 'application/pdf');

    $this->post(route('public.abstracts.store', $this->call->public_token), [
        'title' => 'With Slides',
        'abstract_body' => 'Body',
        'speaker_name' => 'Someone',
        'speaker_email' => 'someone@example.com',
        'file' => $file,
    ]);

    $submission = AbstractSubmission::where('title', 'With Slides')->firstOrFail();
    expect($submission->disk_path)->not->toBeNull();
    expect($submission->original_filename)->toBe('slides.pdf');
    Storage::disk('local')->assertExists($submission->disk_path);
});

test('a non-pdf attachment is rejected', function () {
    $file = UploadedFile::fake()->create('slides.docx', 200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $this->post(route('public.abstracts.store', $this->call->public_token), [
        'title' => 'Wrong File Type',
        'abstract_body' => 'Body',
        'speaker_name' => 'Someone',
        'speaker_email' => 'someone@example.com',
        'file' => $file,
    ])->assertSessionHasErrors('file');

    expect(AbstractSubmission::where('title', 'Wrong File Type')->exists())->toBeFalse();
});

test('an attachment larger than 5mb is rejected', function () {
    $file = UploadedFile::fake()->create('slides.pdf', 5121, 'application/pdf');

    $this->post(route('public.abstracts.store', $this->call->public_token), [
        'title' => 'Too Big',
        'abstract_body' => 'Body',
        'speaker_name' => 'Someone',
        'speaker_email' => 'someone@example.com',
        'file' => $file,
    ])->assertSessionHasErrors('file');

    expect(AbstractSubmission::where('title', 'Too Big')->exists())->toBeFalse();
});

test('the status page is publicly reachable by token and reports current status', function () {
    $submission = AbstractSubmission::factory()->create([
        'abstract_call_id' => $this->call->id,
        'status' => 'Accepted',
        'decision_notes' => 'Great talk, welcome aboard.',
    ]);

    $response = $this->get(route('public.abstracts.status', $submission->public_token));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Public/CallForAbstracts/Status')
        ->where('submission.status', 'Accepted')
        ->where('submission.decision_notes', 'Great talk, welcome aboard.')
        ->missing('submission.disk_path')
        ->missing('submission.public_token')
    );
});

test('an unknown submission token 404s', function () {
    $this->get('/submission/not-a-real-token')->assertNotFound();
});

test('excessive submission attempts are throttled', function () {
    $payload = [
        'title' => 'Rate Limited Talk',
        'abstract_body' => 'Body',
        'speaker_name' => 'Someone',
        'speaker_email' => 'someone@example.com',
    ];

    for ($i = 0; $i < 10; $i++) {
        $this->post(route('public.abstracts.store', $this->call->public_token), $payload);
    }

    $this->post(route('public.abstracts.store', $this->call->public_token), $payload)
        ->assertStatus(429);
});
