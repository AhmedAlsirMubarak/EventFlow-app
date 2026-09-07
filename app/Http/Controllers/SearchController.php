<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\Organization;
use App\Models\StakeholderRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $user = $request->user();

        $results = [
            'events' => [],
            'documents' => [],
            'tasks' => [],
            'requests' => [],
            'organizations' => [],
            'contacts' => [],
        ];

        if (mb_strlen($query) >= 2) {
            $accessibleEventIds = Event::query()->accessibleTo($user)->pluck('id');

            $results['events'] = Event::query()
                ->accessibleTo($user)
                ->where(fn ($q) => $q->whereLike('name', "%{$query}%")->orWhereLike('code', "%{$query}%"))
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'code']);

            $results['documents'] = Document::query()
                ->whereIn('event_id', $accessibleEventIds)
                ->whereLike('title', "%{$query}%")
                ->with('event:id,name')
                ->orderBy('title')
                ->limit(10)
                ->get(['id', 'title', 'event_id']);

            $results['tasks'] = Task::query()
                ->whereIn('event_id', $accessibleEventIds)
                ->whereLike('title', "%{$query}%")
                ->with('event:id,name')
                ->orderBy('title')
                ->limit(10)
                ->get(['id', 'title', 'event_id']);

            $results['requests'] = StakeholderRequest::query()
                ->whereIn('event_id', $accessibleEventIds)
                ->whereLike('title', "%{$query}%")
                ->with('event:id,name')
                ->orderBy('title')
                ->limit(10)
                ->get(['id', 'title', 'event_id']);

            $results['organizations'] = Organization::query()
                ->whereLike('name', "%{$query}%")
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name']);

            $results['contacts'] = Contact::query()
                ->where(fn ($q) => $q->whereLike('full_name', "%{$query}%")->orWhereLike('email', "%{$query}%"))
                ->with('organization:id,name')
                ->orderBy('full_name')
                ->limit(10)
                ->get(['id', 'full_name', 'organization_id']);
        }

        return Inertia::render('Search/Index', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
