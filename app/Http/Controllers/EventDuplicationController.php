<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\TaskStatus;
use App\Http\Requests\Admin\DuplicateEventRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Creates a new event from an existing one — the "template" for recurring
 * events (e.g. next year's edition). Carries over the reusable setup and
 * deliberately leaves behind anything tied to the specific edition: documents,
 * meetings, decisions, requests, comments, abstract submissions, and which
 * exhibitor sat in which booth.
 */
class EventDuplicationController extends Controller
{
    public function store(DuplicateEventRequest $request, Event $event): RedirectResponse
    {
        $include = collect($request->validated('include', []));
        $userId = $request->user()->id;
        $newStart = $request->validated('start_date') ? Carbon::parse($request->validated('start_date')) : null;

        // Every carried-over date moves by the same gap as the start date, so a task
        // due "two weeks before opening" stays two weeks before the new opening.
        $offsetDays = $newStart && $event->start_date ? (int) $event->start_date->diffInDays($newStart, false) : null;
        $shift = fn (?CarbonInterface $date) => $date && $offsetDays !== null ? $date->copy()->addDays($offsetDays) : null;

        $copy = DB::transaction(function () use ($event, $request, $include, $userId, $newStart, $shift) {
            $copy = Event::create([
                'name' => $request->validated('name'),
                'code' => $request->validated('code'),
                'description' => $event->description,
                'logo_path' => $this->copyFile($event->logo_path, 'event-logos'),
                'start_date' => $newStart,
                'end_date' => $shift($event->end_date),
                'status' => EventStatus::Planning,
                'created_by' => $userId,
            ]);

            $members = $include->contains('team')
                ? $event->users->mapWithKeys(fn ($user) => [
                    $user->id => ['role_in_event' => $user->pivot->role_in_event, 'added_by' => $userId],
                ])->all()
                : [];
            $members[$userId] ??= ['role_in_event' => null, 'added_by' => $userId];
            $copy->users()->attach($members);

            if ($include->contains('organizations')) {
                $copy->organizations()->attach(
                    $event->organizations->mapWithKeys(fn ($organization) => [
                        $organization->id => ['relationship_type' => $organization->pivot->relationship_type],
                    ])->all()
                );
            }

            if ($include->contains('tasks')) {
                foreach ($event->tasks()->with('checklistItems')->get() as $task) {
                    $newTask = $copy->tasks()->create([
                        'title' => $task->title,
                        'description' => $task->description,
                        'department' => $task->department,
                        // Keep assignees/organizations only when they came along too,
                        // otherwise the task would point at someone with no access.
                        'assigned_to' => $include->contains('team') ? $task->assigned_to : null,
                        'organization_id' => $include->contains('organizations') ? $task->organization_id : null,
                        'priority' => $task->priority,
                        'start_date' => $shift($task->start_date),
                        'due_date' => $shift($task->due_date),
                        'status' => TaskStatus::Backlog,
                        'created_by' => $userId,
                    ]);

                    $newTask->checklistItems()->createMany(
                        $task->checklistItems->map(fn ($item) => [
                            'label' => $item->label,
                            'position' => $item->position,
                            'is_done' => false,
                        ])->all()
                    );
                }
            }

            if ($include->contains('floor_plans')) {
                foreach ($event->floorPlans()->with('booths')->get() as $floorPlan) {
                    $newPlan = $copy->floorPlans()->create([
                        'name' => $floorPlan->name,
                        'image_path' => $this->copyFile($floorPlan->image_path, 'floor-plans'),
                        'created_by' => $userId,
                    ]);

                    // Same layout, but every booth starts available — who exhibits
                    // where is decided fresh for each edition.
                    $newPlan->booths()->createMany(
                        $floorPlan->booths->map(fn ($booth) => $booth->only(['booth_number', 'x', 'y', 'width', 'height']))->all()
                    );
                }
            }

            if ($include->contains('abstracts')) {
                if ($call = $event->abstractCall()->with('tracks')->first()) {
                    $newCall = $copy->abstractCall()->create([
                        'instructions' => $call->instructions,
                        'opens_at' => $shift($call->opens_at),
                        'closes_at' => $shift($call->closes_at),
                        'is_open' => false,
                        'public_token' => Str::random(40),
                    ]);

                    $newCall->tracks()->createMany(
                        $call->tracks->map(fn ($track) => $track->only(['name', 'description']))->all()
                    );
                }

                $copy->committeeMembers()->createMany(
                    $event->committeeMembers->map(fn ($member) => $member->only(['user_id', 'committee_role']))->all()
                );
            }

            ActivityLog::log($copy, "Event created from template: {$event->name}", $copy->id, $userId);

            return $copy;
        });

        return to_route('events.show', $copy)->with('status', "Created {$copy->name} from {$event->name}.");
    }

    /**
     * Give the new event its own copy of a public-disk file, so deleting or
     * replacing it on one event never breaks the other.
     */
    private function copyFile(?string $path, string $directory): ?string
    {
        $disk = Storage::disk('public');

        if (! $path || ! $disk->exists($path)) {
            return null;
        }

        $newPath = $directory.'/'.Str::random(40).'.'.pathinfo($path, PATHINFO_EXTENSION);
        $disk->copy($path, $newPath);

        return $newPath;
    }
}
