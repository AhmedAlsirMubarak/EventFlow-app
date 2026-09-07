<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Database\Factories\AbstractSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'abstract_call_id', 'abstract_track_id', 'reference', 'title', 'abstract_body', 'keywords',
    'speaker_name', 'speaker_email', 'speaker_phone', 'speaker_bio', 'speaker_organization',
    'status', 'decided_by', 'decided_at', 'decision_notes', 'public_token', 'submitted_at',
    'disk_path', 'original_filename', 'mime_type', 'size',
])]
class AbstractSubmission extends Model
{
    /** @use HasFactory<AbstractSubmissionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<AbstractCall, $this>
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(AbstractCall::class, 'abstract_call_id');
    }

    /**
     * @return BelongsTo<AbstractTrack, $this>
     */
    public function track(): BelongsTo
    {
        return $this->belongsTo(AbstractTrack::class, 'abstract_track_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function hasAttachment(): bool
    {
        return $this->disk_path !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'decided_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }
}
