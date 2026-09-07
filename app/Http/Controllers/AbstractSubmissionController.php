<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\DecideAbstractSubmissionRequest;
use App\Models\AbstractSubmission;
use App\Notifications\AbstractDecisionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbstractSubmissionController extends Controller
{
    public function download(AbstractSubmission $abstractSubmission): StreamedResponse
    {
        Gate::authorize('view', $abstractSubmission);

        abort_unless($abstractSubmission->hasAttachment(), 404);

        return Storage::disk('local')->download($abstractSubmission->disk_path, $abstractSubmission->original_filename);
    }

    public function decide(DecideAbstractSubmissionRequest $request, AbstractSubmission $abstractSubmission): RedirectResponse
    {
        $abstractSubmission->update([
            'status' => $request->validated('status'),
            'decision_notes' => $request->validated('decision_notes'),
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        Notification::route('mail', $abstractSubmission->speaker_email)
            ->notify(new AbstractDecisionNotification($abstractSubmission));

        return to_route('events.show', ['event' => $abstractSubmission->call->event_id, 'tab' => 'abstracts'])
            ->with('status', 'Decision recorded.');
    }
}
