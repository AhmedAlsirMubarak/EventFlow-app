<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Http\Requests\Admin\StoreAbstractReviewRequest;
use App\Models\AbstractSubmission;
use Illuminate\Http\RedirectResponse;

class AbstractReviewController extends Controller
{
    public function store(StoreAbstractReviewRequest $request, AbstractSubmission $abstractSubmission): RedirectResponse
    {
        $abstractSubmission->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        if ($abstractSubmission->status === SubmissionStatus::Submitted) {
            $abstractSubmission->update(['status' => SubmissionStatus::UnderReview]);
        }

        return to_route('events.show', ['event' => $abstractSubmission->call->event_id, 'tab' => 'abstracts'])
            ->with('status', 'Review saved.');
    }
}
