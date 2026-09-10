<?php

namespace App\Http\Controllers;

use App\Enums\CommitteeRole;
use App\Enums\SubmissionStatus;
use App\Http\Requests\Admin\StoreAbstractReviewRequest;
use App\Models\AbstractSubmission;
use App\Models\CommitteeMember;
use App\Notifications\AbstractReviewSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class AbstractReviewController extends Controller
{
    public function store(StoreAbstractReviewRequest $request, AbstractSubmission $abstractSubmission): RedirectResponse
    {
        $review = $abstractSubmission->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        if ($abstractSubmission->status === SubmissionStatus::Submitted) {
            $abstractSubmission->update(['status' => SubmissionStatus::UnderReview]);
        }

        $chairs = CommitteeMember::query()
            ->where('event_id', $abstractSubmission->call->event_id)
            ->where('committee_role', CommitteeRole::Chair)
            ->where('user_id', '!=', $request->user()->id)
            ->with('user')
            ->get()
            ->pluck('user');

        Notification::send($chairs, new AbstractReviewSubmittedNotification($review));

        return to_route('events.show', ['event' => $abstractSubmission->call->event_id, 'tab' => 'abstracts'])
            ->with('status', 'Review saved.');
    }
}
