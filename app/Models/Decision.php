<?php

namespace App\Models;

use Database\Factories\DecisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['reference', 'event_id', 'title', 'description', 'decision_date', 'decided_by', 'organization_id', 'meeting_id', 'document_id', 'reason', 'created_by'])]
class Decision extends Model
{
    /** @use HasFactory<DecisionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Meeting, $this>
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * The single primary document this decision concerns (e.g. the floor plan being approved).
     *
     * @return BelongsTo<Document, $this>
     */
    public function relatedDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Additional supporting attachments beyond the single related document.
     *
     * @return BelongsToMany<Document, $this>
     */
    public function supportingDocuments(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'decision_document')->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision_date' => 'date:Y-m-d',
        ];
    }
}
