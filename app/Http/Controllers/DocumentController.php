<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreDocumentRequest;
use App\Http\Requests\Admin\UpdateDocumentRequest;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, Event $event): RedirectResponse
    {
        $document = DB::transaction(function () use ($request, $event) {
            $document = $event->documents()->create([
                ...$request->safe()->except(['file']),
                'created_by' => $request->user()->id,
            ]);

            $this->storeVersionFor($document, $request);

            return $document;
        });

        ActivityLog::log($document, 'Document uploaded: '.$document->title, $event->id, $request->user()->id);

        return to_route('events.show', ['event' => $event, 'tab' => 'documents'])->with('status', 'Document uploaded.');
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $document->update($request->validated());

        return to_route('events.show', ['event' => $document->event_id, 'tab' => 'documents'])->with('status', 'Document updated.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $eventId = $document->event_id;

        foreach ($document->versions as $version) {
            Storage::disk('local')->delete($version->disk_path);
        }

        $document->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'documents'])->with('status', 'Document removed.');
    }

    /**
     * Store the uploaded file as the document's initial version and mark it current.
     */
    private function storeVersionFor(Document $document, StoreDocumentRequest $request): void
    {
        $file = $request->file('file');
        $path = $file->store('documents/'.$document->id, 'local');

        $version = $document->versions()->create([
            'version_number' => 1,
            'disk_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        $document->update(['current_version_id' => $version->id]);
    }
}
