<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreDecisionRequest;
use App\Http\Requests\Admin\UpdateDecisionRequest;
use App\Models\ActivityLog;
use App\Models\Decision;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DecisionController extends Controller
{
    public function store(StoreDecisionRequest $request, Event $event): RedirectResponse
    {
        $decision = DB::transaction(function () use ($request, $event) {
            $decision = $event->decisions()->create([
                ...$request->safe()->except(['supporting_document_ids']),
                'reference' => 'DEC-'.strtoupper(Str::random(6)),
                'created_by' => $request->user()->id,
            ]);

            $decision->supportingDocuments()->sync($request->validated('supporting_document_ids', []));

            return $decision;
        });

        ActivityLog::log($decision, 'Decision recorded: '.$decision->title, $event->id, $request->user()->id);

        return to_route('events.show', ['event' => $event, 'tab' => 'decisions'])->with('status', 'Decision recorded.');
    }

    public function update(UpdateDecisionRequest $request, Decision $decision): RedirectResponse
    {
        $decision->update($request->safe()->except(['supporting_document_ids']));
        $decision->supportingDocuments()->sync($request->validated('supporting_document_ids', []));

        return to_route('events.show', ['event' => $decision->event_id, 'tab' => 'decisions'])->with('status', 'Decision updated.');
    }

    public function destroy(Decision $decision): RedirectResponse
    {
        Gate::authorize('delete', $decision);

        $eventId = $decision->event_id;
        $decision->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'decisions'])->with('status', 'Decision removed.')
            ->with('undo', RestoreController::undoUrl($decision));
    }
}
