<?php

namespace App\Http\Controllers;

use App\Enums\DocumentActivityType;
use App\Http\Requests\Admin\StoreDocumentShareRequest;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Notifications\DocumentSharedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class DocumentShareController extends Controller
{
    public function store(StoreDocumentShareRequest $request, Document $document): RedirectResponse
    {
        abort_unless($document->isApprovedForSharing(), 403, 'This document must be approved before it can be shared.');

        $share = $document->shares()->create([
            ...$request->validated(),
            'shared_by' => $request->user()->id,
        ]);

        $document->activities()->create([
            'document_share_id' => $share->id,
            'user_id' => $request->user()->id,
            'type' => DocumentActivityType::Shared,
        ]);

        $recipients = $share->recipientContacts()->filter(fn ($contact) => $contact->hasPortalAccess());
        Notification::send($recipients, new DocumentSharedNotification($document));

        return to_route('events.show', ['event' => $document->event_id, 'tab' => 'documents'])->with('status', 'Document shared.');
    }

    public function destroy(DocumentShare $documentShare): RedirectResponse
    {
        abort_unless($documentShare->document, 404); // the document has been deleted
        Gate::authorize('share', $documentShare->document);

        $eventId = $documentShare->document->event_id;
        $documentShare->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'documents'])->with('status', 'Share revoked.');
    }
}
