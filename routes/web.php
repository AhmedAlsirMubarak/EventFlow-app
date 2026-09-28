<?php

use App\Enums\Role;
use App\Http\Controllers\AbstractCallController;
use App\Http\Controllers\AbstractReviewController;
use App\Http\Controllers\AbstractSubmissionController;
use App\Http\Controllers\AbstractTrackController;
use App\Http\Controllers\AcceptedSpeakersExportController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BoothController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunicationReportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactExportController;
use App\Http\Controllers\ContactImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DecisionController;
use App\Http\Controllers\DocumentApprovalController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentShareController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\EventClosureController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventDuplicationController;
use App\Http\Controllers\FloorPlanController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\LessonLearnedController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MyWorkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\Portal\AbstractReviewController as PortalAbstractReviewController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Portal\StakeholderRequestController as PortalStakeholderRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\CallForAbstractsController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\RestoreController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StakeholderRequestController;
use App\Http\Controllers\TaskChecklistItemController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('home');

// Public call-for-abstracts routes: unauthenticated, token-bound, no session required.
Route::get('call/{abstractCall:public_token}', [CallForAbstractsController::class, 'show'])->name('public.abstracts.show');
Route::post('call/{abstractCall:public_token}', [CallForAbstractsController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.abstracts.store');
Route::get('submission/{abstractSubmission:public_token}', [CallForAbstractsController::class, 'status'])->name('public.abstracts.status');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

Route::middleware(['auth', 'verified', 'internal'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/my-work', [MyWorkController::class, 'index'])->name('my-work.index');
    Route::post('/trash/{type}/{id}/restore', [RestoreController::class, 'store'])
        ->whereNumber('id')
        ->name('trash.restore');

    Route::get('/settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])->name('two-factor.show');
    Route::post('/settings/two-factor', [TwoFactorAuthenticationController::class, 'enable'])->name('two-factor.enable');
    Route::post('/settings/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])
        ->middleware('throttle:5,1')
        ->name('two-factor.confirm');
    Route::delete('/settings/two-factor', [TwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.destroy');

    Route::middleware(['role:'.Role::SuperAdministrator->value.'|'.Role::Management->value, 'password.confirm'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::resource('users', UserController::class)->except('show');
        });

    Route::resource('organizations', OrganizationController::class);
    Route::post('organizations/{organization}/contacts', [ContactController::class, 'store'])->name('organizations.contacts.store');
    Route::get('contacts/import-template', [ContactImportController::class, 'template'])->name('contacts.import-template');
    Route::post('organizations/{organization}/contacts/import', [ContactImportController::class, 'store'])->name('organizations.contacts.import');
    Route::get('organizations/{organization}/contacts/export', [ContactExportController::class, 'index'])->name('organizations.contacts.export');
    Route::put('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::post('contacts/{contact}/portal-access', [ContactController::class, 'grantPortalAccess'])->name('contacts.portal-access');
    Route::post('contacts/{contact}/portal-access/resend', [ContactController::class, 'resendPortalAccess'])->name('contacts.portal-access.resend');

    Route::resource('events', EventController::class);
    Route::post('events/{event}/duplicate', [EventDuplicationController::class, 'store'])->name('events.duplicate');
    Route::get('events/{event}/communication-report', [CommunicationReportController::class, 'show'])->name('events.communication-report');
    Route::post('events/{event}/close', [EventClosureController::class, 'close'])->name('events.close');
    Route::post('events/{event}/reopen', [EventClosureController::class, 'reopen'])->name('events.reopen');
    Route::post('events/{event}/team', [EventController::class, 'attachUser'])->name('events.team.store');
    Route::delete('events/{event}/team/{user}', [EventController::class, 'detachUser'])->name('events.team.destroy');
    Route::post('events/{event}/organizations', [EventController::class, 'attachOrganization'])->name('events.organizations.store');
    Route::delete('events/{event}/organizations/{organization}', [EventController::class, 'detachOrganization'])->name('events.organizations.destroy');

    Route::post('events/{event}/committee', [EventController::class, 'attachCommitteeMember'])->name('committee.store');
    Route::delete('committee-members/{committeeMember}', [EventController::class, 'detachCommitteeMember'])->name('committee.destroy');

    Route::post('events/{event}/abstract-call', [AbstractCallController::class, 'store'])->name('abstract-calls.store');
    Route::put('abstract-calls/{abstractCall}', [AbstractCallController::class, 'update'])->name('abstract-calls.update');
    Route::post('abstract-calls/{abstractCall}/tracks', [AbstractTrackController::class, 'store'])->name('abstract-tracks.store');
    Route::put('abstract-tracks/{abstractTrack}', [AbstractTrackController::class, 'update'])->name('abstract-tracks.update');
    Route::delete('abstract-tracks/{abstractTrack}', [AbstractTrackController::class, 'destroy'])->name('abstract-tracks.destroy');

    Route::get('abstract-submissions/{abstractSubmission}/download', [AbstractSubmissionController::class, 'download'])->name('abstract-submissions.download');
    Route::post('abstract-submissions/{abstractSubmission}/reviews', [AbstractReviewController::class, 'store'])->name('abstract-submissions.reviews.store');
    Route::put('abstract-submissions/{abstractSubmission}/decision', [AbstractSubmissionController::class, 'decide'])->name('abstract-submissions.decide');

    Route::get('events/{event}/accepted-speakers.csv', [AcceptedSpeakersExportController::class, 'index'])->name('events.accepted-speakers');

    Route::post('events/{event}/floor-plans', [FloorPlanController::class, 'store'])->name('floor-plans.store');
    Route::put('floor-plans/{floorPlan}', [FloorPlanController::class, 'update'])->name('floor-plans.update');
    Route::delete('floor-plans/{floorPlan}', [FloorPlanController::class, 'destroy'])->name('floor-plans.destroy');
    Route::post('floor-plans/{floorPlan}/booths', [BoothController::class, 'store'])->name('booths.store');
    Route::put('booths/{booth}', [BoothController::class, 'update'])->name('booths.update');
    Route::delete('booths/{booth}', [BoothController::class, 'destroy'])->name('booths.destroy');

    Route::post('events/{event}/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::put('ideas/{idea}', [IdeaController::class, 'update'])->name('ideas.update');
    Route::delete('ideas/{idea}', [IdeaController::class, 'destroy'])->name('ideas.destroy');
    Route::post('ideas/{idea}/comments', [CommentController::class, 'storeForIdea'])->name('ideas.comments.store');

    Route::post('events/{event}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('tasks/{task}/comments', [CommentController::class, 'storeForTask'])->name('tasks.comments.store');

    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::post('tasks/{task}/checklist-items', [TaskChecklistItemController::class, 'store'])->name('checklist-items.store');
    Route::patch('checklist-items/{checklistItem}', [TaskChecklistItemController::class, 'update'])->name('checklist-items.update');
    Route::delete('checklist-items/{checklistItem}', [TaskChecklistItemController::class, 'destroy'])->name('checklist-items.destroy');

    Route::post('events/{event}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('documents/{document}/versions', [DocumentVersionController::class, 'store'])->name('documents.versions.store');
    Route::get('document-versions/{version}/download', [DocumentVersionController::class, 'download'])->name('document-versions.download');

    Route::post('documents/{document}/shares', [DocumentShareController::class, 'store'])->name('documents.shares.store');
    Route::delete('document-shares/{documentShare}', [DocumentShareController::class, 'destroy'])->name('document-shares.destroy');
    Route::put('documents/{document}/approval', [DocumentApprovalController::class, 'update'])->name('documents.approval.update');

    Route::post('events/{event}/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::put('meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
    Route::get('meetings/{meeting}/calendar.ics', [MeetingController::class, 'calendar'])->name('meetings.calendar');
    Route::post('meetings/{meeting}/action-items', [MeetingController::class, 'storeActionItem'])->name('meetings.action-items.store');
    Route::post('meetings/{meeting}/documents', [MeetingController::class, 'attachDocument'])->name('meetings.documents.store');
    Route::delete('meetings/{meeting}/documents/{document}', [MeetingController::class, 'detachDocument'])->name('meetings.documents.destroy');

    Route::post('events/{event}/decisions', [DecisionController::class, 'store'])->name('decisions.store');
    Route::put('decisions/{decision}', [DecisionController::class, 'update'])->name('decisions.update');
    Route::delete('decisions/{decision}', [DecisionController::class, 'destroy'])->name('decisions.destroy');

    Route::post('events/{event}/requests', [StakeholderRequestController::class, 'store'])->name('requests.store');
    Route::put('requests/{stakeholderRequest}', [StakeholderRequestController::class, 'update'])->name('requests.update');
    Route::delete('requests/{stakeholderRequest}', [StakeholderRequestController::class, 'destroy'])->name('requests.destroy');
    Route::post('requests/{stakeholderRequest}/comments', [CommentController::class, 'storeForRequest'])->name('requests.comments.store');

    Route::post('events/{event}/reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::put('reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
    Route::delete('reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');

    Route::post('events/{event}/lessons', [LessonLearnedController::class, 'store'])->name('lessons.store');
    Route::put('lessons/{lessonLearned}', [LessonLearnedController::class, 'update'])->name('lessons.update');
    Route::delete('lessons/{lessonLearned}', [LessonLearnedController::class, 'destroy'])->name('lessons.destroy');
});

Route::middleware(['auth', 'verified', 'external'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('/', [PortalController::class, 'index'])->name('dashboard');
        Route::get('documents/{document}', [PortalController::class, 'show'])->name('documents.show');
        Route::post('documents/{document}/acknowledge', [PortalController::class, 'acknowledge'])->name('documents.acknowledge');
        Route::get('document-versions/{version}/download', [PortalController::class, 'download'])->name('document-versions.download');

        Route::get('requests', [PortalStakeholderRequestController::class, 'index'])->name('requests.index');
        Route::post('requests', [PortalStakeholderRequestController::class, 'store'])->name('requests.store');
        Route::get('requests/{stakeholderRequest}', [PortalStakeholderRequestController::class, 'show'])->name('requests.show');
        Route::post('requests/{stakeholderRequest}/comments', [CommentController::class, 'storeForRequest'])->name('requests.comments.store');

        Route::get('reviews', [PortalAbstractReviewController::class, 'index'])->name('reviews.index');
        Route::get('reviews/{abstractSubmission}', [PortalAbstractReviewController::class, 'show'])->name('reviews.show');
        Route::post('reviews/{abstractSubmission}', [PortalAbstractReviewController::class, 'store'])->name('reviews.store');
        Route::get('reviews/{abstractSubmission}/download', [AbstractSubmissionController::class, 'download'])->name('reviews.download');
    });

require __DIR__.'/auth.php';
