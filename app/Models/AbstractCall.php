<?php

namespace App\Models;

use Database\Factories\AbstractCallFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['event_id', 'instructions', 'opens_at', 'closes_at', 'is_open', 'public_token'])]
class AbstractCall extends Model
{
    /** @use HasFactory<AbstractCallFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<AbstractTrack, $this>
     */
    public function tracks(): HasMany
    {
        return $this->hasMany(AbstractTrack::class);
    }

    /**
     * @return HasMany<AbstractSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AbstractSubmission::class);
    }

    /**
     * Whether the call is currently accepting submissions: the manual toggle is on,
     * and (when set) the current time falls within the open/close window.
     */
    public function isAcceptingSubmissions(): bool
    {
        if (! $this->is_open) {
            return false;
        }

        if ($this->opens_at && $this->opens_at->isFuture()) {
            return false;
        }

        if ($this->closes_at && $this->closes_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'is_open' => 'boolean',
        ];
    }
}
