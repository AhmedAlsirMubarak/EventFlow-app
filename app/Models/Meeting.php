<?php

namespace App\Models;

use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['event_id', 'organization_id', 'title', 'held_at', 'location', 'meeting_link', 'agenda', 'discussion_notes', 'created_by'])]
class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use HasFactory;

    protected $appends = ['provider'];

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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_user')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Contact, $this>
     */
    public function externalParticipants(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'meeting_contact')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Document, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'meeting_document')->withTimestamps();
    }

    /**
     * Action items are Tasks created from this meeting.
     *
     * @return HasMany<Task, $this>
     */
    public function actionItems(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'held_at' => 'datetime',
        ];
    }

    /**
     * A display label for the meeting_link's video provider, inferred from its host —
     * works regardless of whether the link was pasted in manually or (later) generated
     * automatically, since both write to the same column.
     */
    protected function provider(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->meeting_link) {
                return null;
            }

            $host = parse_url($this->meeting_link, PHP_URL_HOST) ?? '';

            return match (true) {
                str_contains($host, 'teams.microsoft.com') => 'Microsoft Teams',
                str_contains($host, 'zoom.us') => 'Zoom',
                str_contains($host, 'meet.google.com') => 'Google Meet',
                default => 'Video Call',
            };
        });
    }

    /**
     * A minimal iCalendar (RFC 5545) VEVENT for this meeting, for "Add to Calendar"
     * downloads. Defaults to a 60-minute duration since there's no stored end time.
     */
    public function toIcs(): string
    {
        $start = $this->held_at->clone()->utc();
        $end = $start->clone()->addHour();
        $escape = fn (?string $value) => str_replace(['\\', "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\\;'], $value ?? '');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//EventFlow//Meetings//EN',
            'BEGIN:VEVENT',
            'UID:meeting-'.$this->id.'@eventflow',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$end->format('Ymd\THis\Z'),
            'SUMMARY:'.$escape($this->title),
            'LOCATION:'.$escape($this->meeting_link ?: $this->location),
            'DESCRIPTION:'.$escape($this->agenda),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }
}
