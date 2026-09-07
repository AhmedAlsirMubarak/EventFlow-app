<?php

namespace App\Models;

use App\Enums\LessonCategory;
use Database\Factories\LessonLearnedFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'category', 'title', 'description', 'department', 'created_by'])]
class LessonLearned extends Model
{
    /** @use HasFactory<LessonLearnedFactory> */
    use HasFactory;

    protected $table = 'lessons_learned';

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
            'category' => LessonCategory::class,
        ];
    }
}
