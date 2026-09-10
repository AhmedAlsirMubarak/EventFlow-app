<?php

use App\Enums\Role as RoleEnum;
use App\Models\AbstractCall;
use App\Models\AbstractSubmission;
use App\Models\CommitteeMember;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AbstractReviewSubmittedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $organization = Organization::factory()->create();

    $this->reviewer = User::factory()->external()->create();
    $this->reviewer->assignRole(RoleEnum::ExternalStakeholder->value);
    Contact::factory()->create(['organization_id' => $organization->id, 'user_id' => $this->reviewer->id]);

    $this->outsider = User::factory()->external()->create();
    $this->outsider->assignRole(RoleEnum::ExternalStakeholder->value);
    Contact::factory()->create(['organization_id' => $organization->id, 'user_id' => $this->outsider->id]);

    $this->chair = User::factory()->create();
    $this->chair->assignRole(RoleEnum::GeneralStaff->value);

    $this->event = Event::factory()->create();
    CommitteeMember::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $this->reviewer->id,
        'committee_role' => 'Reviewer',
    ]);
    CommitteeMember::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $this->chair->id,
        'committee_role' => 'Chair',
    ]);

    $this->call = AbstractCall::factory()->create(['event_id' => $this->event->id]);
    $this->submission = AbstractSubmission::factory()->create(['abstract_call_id' => $this->call->id]);
});

test('an external committee member sees their assigned submissions', function () {
    $response = $this->actingAs($this->reviewer)->get(route('portal.reviews.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Portal/Reviews/Index')
        ->has('submissions', 1)
        ->where('submissions.0.id', $this->submission->id)
    );
});

test('an external user with no committee assignments sees an empty list', function () {
    $response = $this->actingAs($this->outsider)->get(route('portal.reviews.index'));

    $response->assertInertia(fn (Assert $page) => $page->has('submissions', 0));
});

test('an external committee member can score a submission and it moves to under review', function () {
    $response = $this->actingAs($this->reviewer)->post(route('portal.reviews.store', $this->submission), [
        'score' => 4,
        'recommendation' => 'Accept',
        'comments' => 'Solid proposal.',
    ]);

    $response->assertRedirect(route('portal.reviews.show', $this->submission));

    $review = $this->submission->reviews()->where('user_id', $this->reviewer->id)->firstOrFail();
    expect($review->score)->toBe(4);
    expect($this->submission->refresh()->status->value)->toBe('Under Review');
});

test('an external committee member submitting a review notifies the internal chair', function () {
    Notification::fake();

    $this->actingAs($this->reviewer)->post(route('portal.reviews.store', $this->submission), [
        'score' => 4,
        'recommendation' => 'Accept',
    ]);

    Notification::assertSentTo(
        $this->chair,
        AbstractReviewSubmittedNotification::class,
        fn ($notification, $channels) => in_array('mail', $channels) && in_array('database', $channels)
    );
});

test('an external user not on the committee cannot view or review a submission', function () {
    $this->actingAs($this->outsider)
        ->get(route('portal.reviews.show', $this->submission))
        ->assertForbidden();

    $this->actingAs($this->outsider)
        ->post(route('portal.reviews.store', $this->submission), [
            'score' => 3,
            'recommendation' => 'Maybe',
        ])
        ->assertForbidden();
});

test('an external committee member can download a submission attachment', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('slides.pdf', 100, 'application/pdf');
    $path = $file->store('abstract-submissions/'.$this->submission->id, 'local');
    $this->submission->update(['disk_path' => $path, 'original_filename' => 'slides.pdf']);

    $this->actingAs($this->reviewer)
        ->get(route('portal.reviews.download', $this->submission))
        ->assertOk();
});

test('the My Reviews nav link only shows for users with committee assignments', function () {
    $response = $this->actingAs($this->reviewer)->get(route('portal.dashboard'));
    $response->assertInertia(fn (Assert $page) => $page->where('hasCommitteeAssignments', true));

    $response = $this->actingAs($this->outsider)->get(route('portal.dashboard'));
    $response->assertInertia(fn (Assert $page) => $page->where('hasCommitteeAssignments', false));
});
