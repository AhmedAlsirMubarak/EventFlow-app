<?php

namespace App\Http\Controllers;

use App\Models\AbstractSubmission;
use Illuminate\Support\Facades\Gate;
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
}
