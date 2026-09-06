<?php

namespace App\Http\Controllers\Portal;

use App\Enums\DocumentActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreDocumentAcknowledgmentRequest;
use App\Models\Document;
use App\Models\DocumentAcknowledgment;
use App\Models\DocumentVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalController extends Controller
{
    public function index(Request $request): Response
    {
        $contact = $request->user()->contact;

        $shares = $contact->accessibleDocumentShares();

        $documents = $shares->map(function ($share) use ($contact) {
            $document = $share->document;

            $latestActivity = $document->activities()
                ->where('contact_id', $contact->id)
                ->latest()
                ->first();

            $acknowledged = $document->acknowledgment_required
                ? DocumentAcknowledgment::where('document_share_id', $share->id)
                    ->where('contact_id', $contact->id)
                    ->where('document_version_id', $document->current_version_id)
                    ->exists()
                : null;

            return [
                'id' => $document->id,
                'title' => $document->title,
                'category' => $document->category,
                'event_name' => $document->event->name,
                'acknowledgment_required' => $document->acknowledgment_required,
                'acknowledged' => $acknowledged,
                'latest_status' => $latestActivity?->type,
                'current_version' => $document->currentVersion,
            ];
        });

        return Inertia::render('Portal/Dashboard', [
            'documents' => $documents->values(),
        ]);
    }

    public function show(Request $request, Document $document): Response
    {
        $contact = $request->user()->contact;
        $share = $document->shareFor($contact);

        abort_unless($share, 403);

        $document->activities()->create([
            'document_share_id' => $share->id,
            'contact_id' => $contact->id,
            'type' => DocumentActivityType::Viewed,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $acknowledgment = DocumentAcknowledgment::where('document_share_id', $share->id)
            ->where('contact_id', $contact->id)
            ->where('document_version_id', $document->current_version_id)
            ->first();

        return Inertia::render('Portal/Show', [
            'document' => $document->load('currentVersion', 'event:id,name'),
            'acknowledgment' => $acknowledgment,
        ]);
    }

    public function download(Request $request, DocumentVersion $version): StreamedResponse
    {
        $contact = $request->user()->contact;
        $document = $version->document;
        $share = $document->shareFor($contact);

        abort_unless($share, 403);

        $document->activities()->create([
            'document_share_id' => $share->id,
            'contact_id' => $contact->id,
            'type' => DocumentActivityType::Downloaded,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return Storage::disk('local')->download($version->disk_path, $version->original_filename);
    }

    public function acknowledge(StoreDocumentAcknowledgmentRequest $request, Document $document): RedirectResponse
    {
        $contact = $request->user()->contact;
        $share = $document->shareFor($contact);

        abort_unless($share, 403);
        abort_unless($document->acknowledgment_required, 403);

        DocumentAcknowledgment::create([
            'document_share_id' => $share->id,
            'contact_id' => $contact->id,
            'document_version_id' => $document->current_version_id,
            'name' => $request->validated('name'),
            'organization_snapshot' => $contact->organization?->name,
            'statement' => 'I acknowledge that I have received and reviewed this document.',
            'acknowledged_at' => now(),
        ]);

        $document->activities()->create([
            'document_share_id' => $share->id,
            'contact_id' => $contact->id,
            'type' => DocumentActivityType::Acknowledged,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Document acknowledged.');
    }
}
