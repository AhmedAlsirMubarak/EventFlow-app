<?php

namespace App\Http\Controllers\Public;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreAbstractSubmissionRequest;
use App\Models\AbstractCall;
use App\Models\AbstractSubmission;
use App\Models\CommitteeMember;
use App\Models\User;
use App\Notifications\AbstractAwaitingReviewNotification;
use App\Notifications\AbstractSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CallForAbstractsController extends Controller
{
    public function show(AbstractCall $abstractCall): Response
    {
        return Inertia::render('Public/CallForAbstracts/Show', [
            'call' => $abstractCall->load(['event:id,name', 'tracks:id,abstract_call_id,name,description']),
            'isOpen' => $abstractCall->isAcceptingSubmissions(),
        ]);
    }

    public function store(StoreAbstractSubmissionRequest $request, AbstractCall $abstractCall): RedirectResponse
    {
        $submission = DB::transaction(function () use ($request, $abstractCall) {
            $submission = $abstractCall->submissions()->create([
                ...$request->safe()->except('file'),
                'reference' => 'ABS-'.strtoupper(Str::random(6)),
                'status' => SubmissionStatus::Submitted,
                'public_token' => Str::random(40),
                'submitted_at' => now(),
            ]);

            if ($file = $request->file('file')) {
                $path = $file->store('abstract-submissions/'.$submission->id, 'local');

                $submission->update([
                    'disk_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            return $submission;
        });

        Notification::route('mail', $submission->speaker_email)
            ->notify(new AbstractSubmittedNotification($submission));

        $reviewers = User::query()
            ->whereIn('id', CommitteeMember::where('event_id', $abstractCall->event_id)->pluck('user_id'))
            ->get();
        Notification::send($reviewers, new AbstractAwaitingReviewNotification($submission));

        return to_route('public.abstracts.status', $submission->public_token)
            ->with('status', 'Your submission has been received.');
    }

    public function status(AbstractSubmission $abstractSubmission): Response
    {
        $abstractSubmission->load(['track:id,name', 'call.event:id,name']);

        return Inertia::render('Public/CallForAbstracts/Status', [
            // Curated on purpose: the full model carries disk_path and public_token,
            // neither of which belongs on a page reachable with just the status link.
            'submission' => [
                'reference' => $abstractSubmission->reference,
                'title' => $abstractSubmission->title,
                'status' => $abstractSubmission->status,
                'decision_notes' => $abstractSubmission->decision_notes,
                'submitted_at' => $abstractSubmission->submitted_at,
                'track' => $abstractSubmission->track ? ['name' => $abstractSubmission->track->name] : null,
                'call' => ['event' => ['name' => $abstractSubmission->call->event->name]],
            ],
            'status' => session('status'),
        ]);
    }
}
