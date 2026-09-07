<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateDocumentApprovalRequest;
use App\Models\ActivityLog;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;

class DocumentApprovalController extends Controller
{
    public function update(UpdateDocumentApprovalRequest $request, Document $document): RedirectResponse
    {
        $approval = $document->currentApproval();

        abort_if($approval === null || $approval->status->value !== 'Pending', 404, 'No pending approval for this document.');

        $approval->update([
            'reviewed_by' => $request->user()->id,
            'status' => $request->validated('status'),
            'comments' => $request->validated('comments'),
            'reviewed_at' => now(),
        ]);

        ActivityLog::log($document, 'Document '.strtolower($approval->status->value).': '.$document->title, $document->event_id, $request->user()->id);

        return to_route('events.show', ['event' => $document->event_id, 'tab' => 'documents'])->with('status', 'Approval decision recorded.');
    }
}
