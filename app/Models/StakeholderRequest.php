<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\TaskPriority;
use App\Models\Concerns\HasComments;
use Database\Factories\StakeholderRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'organization_id', 'contact_id', 'title', 'description', 'assigned_to', 'priority', 'due_date', 'status', 'created_by'])]
class StakeholderRequest extends Model
{
    /** @use HasFactory<StakeholderRequestFactory> */
    use HasComments, HasFactory;

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The external stakeholder this request concerns (and, when created_by is null, who submitted it).
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * The internal user who logged this request, if any (null when the stakeholder submitted it themselves).
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'priority' => TaskPriority::class,
            'status' => RequestStatus::class,
        ];
    }
}
