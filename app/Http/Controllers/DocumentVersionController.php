<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreDocumentVersionRequest;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Notifications\DocumentVersionUploadedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentVersionController extends Controller
{
    public function store(StoreDocumentVersionRequest $request, Document $document): RedirectResponse
    {
        $version = DB::transaction(function () use ($request, $document) {
            $file = $request->file('file');
            $path = $file->store('documents/'.$document->id, 'local');
            $nextVersionNumber = $document->versions()->max('version_number') + 1;

            $version = $document->versions()->create([
                'version_number' => $nextVersionNumber,
                'disk_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);

            $document->update(['current_version_id' => $version->id]);

            return $version;
        });

        $recipients = $document->shares()->get()
            ->flatMap(fn ($share) => $share->recipientContacts())
            ->unique('id')
            ->filter(fn ($contact) => $contact->hasPortalAccess());
        Notification::send($recipients, new DocumentVersionUploadedNotification($document, $version));

        return to_route('events.show', ['event' => $document->event_id, 'tab' => 'documents'])->with('status', 'New version uploaded.');
    }

    public function download(DocumentVersion $version): StreamedResponse
    {
        Gate::authorize('view', $version->document);

        return Storage::disk('local')->download($version->disk_path, $version->original_filename);
    }
}
