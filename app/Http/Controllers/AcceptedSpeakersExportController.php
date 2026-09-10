<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Concerns\SanitizesCsvRows;
use App\Models\AbstractSubmission;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcceptedSpeakersExportController extends Controller
{
    use SanitizesCsvRows;

    public function index(Event $event): StreamedResponse
    {
        Gate::authorize('viewAny', [AbstractSubmission::class, $event]);

        $submissions = AbstractSubmission::query()
            ->whereHas('call', fn ($q) => $q->where('event_id', $event->id))
            ->where('status', SubmissionStatus::Accepted)
            ->with('track:id,name')
            ->orderBy('speaker_name')
            ->get();

        return response()->streamDownload(function () use ($submissions) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Reference', 'Title', 'Track', 'Speaker Name', 'Email', 'Organization', 'Phone']);

            foreach ($submissions as $submission) {
                fputcsv($handle, $this->sanitizeCsvRow([
                    $submission->reference,
                    $submission->title,
                    $submission->track?->name,
                    $submission->speaker_name,
                    $submission->speaker_email,
                    $submission->speaker_organization,
                    $submission->speaker_phone,
                ]));
            }

            fclose($handle);
        }, 'accepted-speakers-'.$event->code.'.csv');
    }
}
