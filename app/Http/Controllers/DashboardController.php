<?php

namespace App\Http\Controllers;

use App\Enums\DocumentActivityType;
use App\Enums\EventStatus;
use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $eventId = $request->integer('event_id') ?: null;
        $organizationId = $request->integer('organization_id') ?: null;
        $from = $request->date('from');
        $to = $request->date('to');

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_users' => User::query()->count(),
                'internal_users' => User::query()->where('user_type', UserType::Internal)->count(),
                'external_users' => User::query()->where('user_type', UserType::External)->count(),
                'roles_configured' => Role::query()->count(),
                ...$this->managementIndicators($eventId, $organizationId, $from, $to),
            ],
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
            'documents_awaiting_acknowledgment' => (clone $documentQuery)
                ->where('acknowledgment_required', true)
                ->whereHas('shares')
                ->whereDoesntHave('activities', fn ($q) => $q->where('type', DocumentActivityType::Acknowledged))
                ->count(),
            'pending_approvals' => 0,
            'open_stakeholder_requests' => 0,
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
