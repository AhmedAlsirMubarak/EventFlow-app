<?php

namespace App\Models;

use App\Enums\IdeaStatus;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\Restorable;
use Database\Factories\IdeaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'title', 'description', 'category', 'status', 'created_by'])]
class Idea extends Model
{
    /** @use HasFactory<IdeaFactory> */
    use HasComments, HasFactory, Restorable;

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
            'status' => IdeaStatus::class,
        ];
    }
}
