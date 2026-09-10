<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\Role;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'code', 'description', 'logo_path', 'start_date', 'end_date', 'status', 'created_by', 'closure_summary', 'closed_at', 'closed_by'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $appends = ['logo_url'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role_in_event', 'added_by')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('relationship_type')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Idea, $this>
     */
    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<Meeting, $this>
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    /**
     * @return HasMany<Decision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    /**
     * @return HasMany<StakeholderRequest, $this>
     */
    public function stakeholderRequests(): HasMany
    {
        return $this->hasMany(StakeholderRequest::class);
    }

    /**
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * @return HasMany<LessonLearned, $this>
     */
    public function lessonsLearned(): HasMany
    {
        return $this->hasMany(LessonLearned::class);
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * @return HasMany<CommitteeMember, $this>
     */
    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    /**
     * @return HasOne<AbstractCall, $this>
     */
    public function abstractCall(): HasOne
    {
        return $this->hasOne(AbstractCall::class);
    }

    /**
     * @return HasMany<FloorPlan, $this>
     */
    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * Public URL for the event's logo, if one has been uploaded.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
        );
    }

    /**
     * Scope to events the given user can access: elevated roles see every event,
     * everyone else only the events they're an assigned team member of.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value])) {
            return $query;
        }

        return $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'status' => EventStatus::class,
            'closed_at' => 'datetime',
        ];
    }
}
