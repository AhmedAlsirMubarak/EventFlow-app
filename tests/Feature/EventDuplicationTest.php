<?php

use App\Enums\EventStatus;
use App\Enums\Role as RoleEnum;
use App\Enums\TaskStatus;
use App\Models\AbstractCall;
use App\Models\AbstractSubmission;
use App\Models\AbstractTrack;
use App\Models\Booth;
use App\Models\CommitteeMember;
use App\Models\Document;
use App\Models\Event;
use App\Models\FloorPlan;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->user->assignRole(RoleEnum::GeneralStaff->value);
    $this->colleague = User::factory()->create();

    $this->source = Event::factory()->create([
        'name' => 'Oman Climate Week 2026',
        'code' => 'ocw-2026',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-05',
        'status' => EventStatus::Completed,
    ]);
    $this->source->users()->attach($this->user->id, ['role_in_event' => 'Lead']);
    $this->source->users()->attach($this->colleague->id, ['role_in_event' => 'Logistics']);

    $this->organization = Organization::factory()->create();
    $this->source->organizations()->attach($this->organization->id, ['relationship_type' => 'Sponsor']);

    $this->task = Task::factory()->create([
        'event_id' => $this->source->id,
        'title' => 'Book the venue',
        'assigned_to' => $this->colleague->id,
        'organization_id' => $this->organization->id,
        'start_date' => '2026-02-01',
        'due_date' => '2026-02-15',
        'status' => TaskStatus::Completed,
    ]);
    $this->task->checklistItems()->create(['label' => 'Sign contract', 'is_done' => true, 'position' => 0]);

    Storage::disk('public')->put('floor-plans/hall.png', 'image');
    $plan = FloorPlan::factory()->create(['event_id' => $this->source->id, 'name' => 'Hall A', 'image_path' => 'floor-plans/hall.png']);
    Booth::factory()->create([
        'floor_plan_id' => $plan->id,
        'booth_number' => 'A1',
        'organization_id' => $this->organization->id,
        'notes' => 'Paid in full',
    ]);

    $call = AbstractCall::factory()->create(['event_id' => $this->source->id, 'is_open' => true]);
    AbstractTrack::factory()->create(['abstract_call_id' => $call->id, 'name' => 'Energy']);
    AbstractSubmission::factory()->create(['abstract_call_id' => $call->id]);
    CommitteeMember::factory()->create(['event_id' => $this->source->id, 'user_id' => $this->colleague->id, 'committee_role' => 'Chair']);

    Document::factory()->create(['event_id' => $this->source->id]);
});

function duplicateSourceEvent($test, array $include = ['team', 'organizations', 'tasks', 'floor_plans', 'abstracts'])
{
    return $test->actingAs($test->user)->post(route('events.duplicate', $test->source), [
        'name' => 'Oman Climate Week 2027',
        'code' => 'ocw-2027',
        'start_date' => '2027-03-01',
        'include' => $include,
    ]);
}

test('duplicating an event carries over the reusable setup and shifts dates', function () {
    $response = duplicateSourceEvent($this);

    $copy = Event::where('code', 'ocw-2027')->firstOrFail();
    $response->assertRedirect(route('events.show', $copy));

    expect($copy->status)->toBe(EventStatus::Planning);
    expect($copy->end_date->toDateString())->toBe('2027-03-05');

    expect($copy->users()->pluck('users.id')->sort()->values()->all())
        ->toBe(collect([$this->user->id, $this->colleague->id])->sort()->values()->all());
    expect($copy->users()->find($this->colleague->id)->pivot->role_in_event)->toBe('Logistics');
    expect($copy->organizations()->first()->pivot->relationship_type)->toBe('Sponsor');

    $task = $copy->tasks()->with('checklistItems')->firstOrFail();
    expect($task->title)->toBe('Book the venue');
    expect($task->status)->toBe(TaskStatus::Backlog);
    expect($task->start_date->toDateString())->toBe('2027-02-01');
    expect($task->due_date->toDateString())->toBe('2027-02-15');
    expect($task->assigned_to)->toBe($this->colleague->id);
    expect($task->checklistItems->first()->is_done)->toBeFalse();

    $plan = $copy->floorPlans()->with('booths')->firstOrFail();
    expect($plan->image_path)->not->toBe('floor-plans/hall.png');
    Storage::disk('public')->assertExists($plan->image_path);
    expect($plan->booths->first()->booth_number)->toBe('A1');
    expect($plan->booths->first()->organization_id)->toBeNull();
    expect($plan->booths->first()->notes)->toBeNull();

    $call = $copy->abstractCall;
    expect($call->is_open)->toBeFalse();
    expect($call->public_token)->not->toBe($this->source->abstractCall->public_token);
    expect($call->tracks()->pluck('name')->all())->toBe(['Energy']);
    expect($call->submissions()->count())->toBe(0);
    expect($copy->committeeMembers()->count())->toBe(1);

    expect($copy->documents()->count())->toBe(0);
});

test('only the chosen parts are carried over, and assignees are dropped without the team', function () {
    duplicateSourceEvent($this, ['tasks']);

    $copy = Event::where('code', 'ocw-2027')->firstOrFail();

    expect($copy->users()->pluck('users.id')->all())->toBe([$this->user->id]);
    expect($copy->organizations()->count())->toBe(0);
    expect($copy->floorPlans()->count())->toBe(0);
    expect($copy->abstractCall)->toBeNull();

    $task = $copy->tasks()->firstOrFail();
    expect($task->assigned_to)->toBeNull();
    expect($task->organization_id)->toBeNull();
});

test('the new event code must be unique', function () {
    $this->actingAs($this->user)->post(route('events.duplicate', $this->source), [
        'name' => 'Clash',
        'code' => 'ocw-2026',
    ])->assertSessionHasErrors('code');
});

test('you cannot duplicate an event you cannot see', function () {
    $outsider = User::factory()->create();
    $outsider->assignRole(RoleEnum::GeneralStaff->value);

    $this->actingAs($outsider)->post(route('events.duplicate', $this->source), [
        'name' => 'Stolen',
        'code' => 'stolen',
    ])->assertForbidden();
});

test('the logo gets its own copy', function () {
    $logo = UploadedFile::fake()->image('logo.png')->store('event-logos', 'public');
    $this->source->update(['logo_path' => $logo]);

    duplicateSourceEvent($this, []);

    $copy = Event::where('code', 'ocw-2027')->firstOrFail();
    expect($copy->logo_path)->not->toBeNull()->not->toBe($logo);
    Storage::disk('public')->assertExists($copy->logo_path);
});
