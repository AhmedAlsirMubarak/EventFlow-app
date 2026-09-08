<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DocumentActivity;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $eventId = $request->integer('event_id') ?: null;

        return Inertia::render('Activity/Index', [
            'activity' => $this->recentActivity($eventId),
            'filters' => [
                'event_id' => $eventId,
            ],
            'filterOptions' => [
                'events' => Event::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function recentActivity(?int $eventId): Collection
    {
        $activityLogs = ActivityLog::query()
            ->with('causer:id,name,avatar_path')
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'description' => $log->description,
                'actor' => $log->causer?->name,
                'actor_avatar_url' => $log->causer?->avatar_url,
                'created_at' => $log->created_at,
            ]);

        $documentActivities = DocumentActivity::query()
            ->with(['document:id,title,event_id', 'user:id,name,avatar_path', 'contact:id,full_name'])
            ->when($eventId, fn ($q) => $q->whereHas('document', fn ($q) => $q->where('event_id', $eventId)))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (DocumentActivity $activity) => [
                'description' => "Document {$activity->type->value}: {$activity->document->title}",
                'actor' => $activity->user?->name ?? $activity->contact?->full_name,
                'actor_avatar_url' => $activity->user?->avatar_url,
                'created_at' => $activity->created_at,
            ]);

        return $activityLogs->concat($documentActivities)
            ->sortByDesc('created_at')
            ->take(50)
            ->values();
    }
}
