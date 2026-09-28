<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentActivityType;
use App\Enums\EventStatus;
use App\Enums\RequestStatus;
use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\Event;
use App\Models\Organization;
use App\Models\StakeholderRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * How long a given filter combination's stats stay cached. Long enough to absorb
     * the dashboard's 15s poll (see resources/js/pollInterval.js), short enough that
     * new data and filter changes show up within a minute.
     */
    private const STATS_CACHE_SECONDS = 60;

    public function index(Request $request): Response
    {
        $eventId = $request->integer('event_id') ?: null;
        $organizationId = $request->integer('organization_id') ?: null;
        $from = $request->date('from');
        $to = $request->date('to');

        $cacheKey = sprintf(
            'dashboard:stats:%s:%s:%s:%s',
            $eventId ?? 'all',
            $organizationId ?? 'all',
            $from?->toDateString() ?? 'none',
            $to?->toDateString() ?? 'none',
        );

        return Inertia::render('Dashboard', [
            'stats' => Cache::remember(
                $cacheKey,
                self::STATS_CACHE_SECONDS,
                fn () => [
                    'total_users' => User::query()->count(),
                    'internal_users' => User::query()->where('user_type', UserType::Internal)->count(),
                    'external_users' => User::query()->where('user_type', UserType::External)->count(),
                    'roles_configured' => Role::query()->count(),
                    ...$this->managementIndicators($eventId, $organizationId, $from, $to),
                ],
            ),
            'charts' => Cache::remember(
                $cacheKey.':charts',
                self::STATS_CACHE_SECONDS,
                fn () => [
                    'tasks_by_status' => $this->tasksByStatus($eventId, $organizationId),
                    'activity_trend' => $this->activityTrend($eventId),
                ],
            ),
            // Per-user (scoped to the events you can open), so never shared via the cache.
            'upcomingEvents' => $this->upcomingEvents($request->user(), $eventId, $organizationId),
            'attentionTasks' => $this->attentionTasks($request->user(), $eventId, $organizationId),
            'filters' => [
                'event_id' => $eventId,
                'organization_id' => $organizationId,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'filterOptions' => [
                'events' => Event::query()->orderBy('name')->get(['id', 'name']),
                'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function managementIndicators(?int $eventId, ?int $organizationId, ?Carbon $from, ?Carbon $to): array
    {
        $eventQuery = Event::query()->when($organizationId, fn ($q) => $q->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId)));
        $this->applyDateRange($eventQuery, 'start_date', $from, $to);

        $documentQuery = Document::query()
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId));

        $taskQuery = Task::query()
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId));

        return [
            'active_events' => (clone $eventQuery)->where('status', EventStatus::Active)->count(),
            'participating_organizations' => $eventId
                ? Event::findOrFail($eventId)->organizations()->count()
                : Organization::query()->count(),
            'total_stakeholders' => Contact::query()
                ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
                ->count(),
            'documents_shared' => (clone $documentQuery)->whereHas('shares')->count(),
            'documents_viewed' => (clone $documentQuery)->whereHas(
                'activities',
                fn ($q) => $q->where('type', DocumentActivityType::Viewed)
            )->count(),
            'documents_acknowledged' => (clone $documentQuery)->whereHas(
                'activities',
                fn ($q) => $q->where('type', DocumentActivityType::Acknowledged)
            )->count(),
            'open_tasks' => (clone $taskQuery)->where('status', '!=', TaskStatus::Completed)->count(),
            'documents_awaiting_acknowledgment' => (clone $documentQuery)
                ->where('acknowledgment_required', true)
                ->whereHas('shares')
                ->whereDoesntHave('activities', fn ($q) => $q->where('type', DocumentActivityType::Acknowledged))
                ->count(),
            'pending_approvals' => DocumentApproval::query()
                ->where('status', ApprovalStatus::Pending)
                ->whereHas('document', fn ($q) => $q
                    ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
                    ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId)))
                ->count(),
            'open_stakeholder_requests' => StakeholderRequest::query()
                ->whereNotIn('status', [RequestStatus::Completed, RequestStatus::Cancelled])
                ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
                ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
                ->count(),
            'overdue_tasks' => (clone $taskQuery)
                ->where('status', '!=', TaskStatus::Completed)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->count(),
            'upcoming_deadlines' => (clone $taskQuery)
                ->where('status', '!=', TaskStatus::Completed)
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->count(),
        ];
    }

    /**
     * Task count per status, in the workflow's own order (Backlog → Completed),
     * including statuses with no tasks so the chart's rows never jump around.
     *
     * @return list<array{status: string, count: int}>
     */
    private function tasksByStatus(?int $eventId, ?int $organizationId): array
    {
        $counts = Task::query()
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return array_map(
            fn (TaskStatus $status) => ['status' => $status->value, 'count' => (int) ($counts[$status->value] ?? 0)],
            TaskStatus::cases(),
        );
    }

    /**
     * Activity-log entries per day for the last 30 days (every day present,
     * zeros included), for the dashboard's trend chart.
     *
     * @return list<array{date: string, count: int}>
     */
    private function activityTrend(?int $eventId): array
    {
        $start = now()->subDays(29)->startOfDay();

        $counts = ActivityLog::query()
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, 29))
            ->map(function (int $offset) use ($start, $counts) {
                $day = $start->copy()->addDays($offset)->toDateString();

                return ['date' => $day, 'count' => (int) ($counts[$day] ?? 0)];
            })
            ->all();
    }

    /**
     * The next few events you can open that haven't finished yet.
     *
     * @return Collection<int, Event>
     */
    private function upcomingEvents(User $user, ?int $eventId, ?int $organizationId): Collection
    {
        return Event::query()
            ->accessibleTo($user)
            ->when($eventId, fn ($q) => $q->whereKey($eventId))
            ->when($organizationId, fn ($q) => $q->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId)))
            ->whereNotIn('status', [EventStatus::Completed, EventStatus::Cancelled])
            ->where(fn ($q) => $q
                ->where('end_date', '>=', now()->toDateString())
                ->orWhere(fn ($q) => $q->whereNull('end_date')->where('start_date', '>=', now()->toDateString())))
            ->orderBy('start_date')
            ->limit(5)
            ->get(['id', 'name', 'status', 'start_date', 'end_date', 'logo_path']);
    }

    /**
     * Open tasks that are overdue or due within a week, across the events you
     * can open — the "what needs chasing" list.
     *
     * @return Collection<int, Task>
     */
    private function attentionTasks(User $user, ?int $eventId, ?int $organizationId): Collection
    {
        return Task::query()
            ->whereIn('event_id', Event::query()->accessibleTo($user)->select('id'))
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->where('status', '!=', TaskStatus::Completed)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays(7)->toDateString())
            ->with(['event:id,name', 'assignee:id,name,avatar_path'])
            ->orderBy('due_date')
            ->limit(6)
            ->get(['id', 'event_id', 'title', 'status', 'priority', 'due_date', 'assigned_to']);
    }

    private function applyDateRange(Builder $query, string $column, ?Carbon $from, ?Carbon $to): void
    {
        if ($from) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query->whereDate($column, '<=', $to);
        }
    }
}
