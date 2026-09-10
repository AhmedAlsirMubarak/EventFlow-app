<?php

namespace App\Http\Controllers\Portal;

use App\Enums\CommitteeRole;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAbstractReviewRequest;
use App\Models\AbstractSubmission;
use App\Models\CommitteeMember;
use App\Notifications\AbstractReviewSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class AbstractReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $eventIds = CommitteeMember::query()->where('user_id', $request->user()->id)->pluck('event_id');

        return Inertia::render('Portal/Reviews/Index', [
            'submissions' => AbstractSubmission::query()
                ->whereHas('call', fn ($q) => $q->whereIn('event_id', $eventIds))
                ->with([
                    'call.event:id,name',
                    'track:id,name',
                    'reviews' => fn ($q) => $q->where('user_id', $request->user()->id),
                ])
                ->orderByDesc('submitted_at')
                ->get(),
        ]);
    }

    public function show(Request $request, AbstractSubmission $abstractSubmission): Response
    {
        Gate::authorize('view', $abstractSubmission);

        return Inertia::render('Portal/Reviews/Show', [
            'submission' => $abstractSubmission->load([
                'call.event:id,name',
                'track:id,name',
                'reviews' => fn ($q) => $q->where('user_id', $request->user()->id),
            ]),
        ]);
    }

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

        return to_route('portal.reviews.show', $abstractSubmission)->with('status', 'Review saved.');
    }
}
