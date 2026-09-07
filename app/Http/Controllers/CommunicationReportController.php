<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CommunicationReportController extends Controller
{
    public function show(Request $request, Event $event): Response
    {
        Gate::authorize('view', $event);

        $organizationId = $request->query('organization_id');
        $organization = $organizationId ? Organization::findOrFail($organizationId) : null;

        $shareFilter = function ($query) use ($organizationId) {
            if ($organizationId) {
                $query->where(fn ($q) => $q
                    ->where('organization_id', $organizationId)
                    ->orWhereHas('contact', fn ($c) => $c->where('organization_id', $organizationId)));
            }
        };

        $documents = $event->documents()
            ->whereHas('shares', $shareFilter)
            ->with([
                'currentVersion',
                'shares' => $shareFilter,
                'shares.organization',
                'shares.contact.organization',
                'shares.sharer:id,name',
                'shares.activities' => fn ($query) => $query->orderBy('created_at'),
                'shares.activities.contact:id,full_name',
                'shares.activities.user:id,name',
                'shares.acknowledgments.contact:id,full_name',
            ])
            ->orderBy('title')
            ->get();

        $pdf = Pdf::loadView('reports.communication-evidence', [
            'event' => $event,
            'organization' => $organization,
            'documents' => $documents,
            'generatedAt' => now(),
        ]);

        $filename = 'communication-evidence-'.$event->code
            .($organization ? '-'.Str::slug($organization->name) : '')
            .'.pdf';

        return $pdf->download($filename);
    }
}
