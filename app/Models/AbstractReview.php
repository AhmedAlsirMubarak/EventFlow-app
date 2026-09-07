<?php

namespace App\Models;

use App\Enums\ReviewRecommendation;
use Database\Factories\AbstractReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['abstract_submission_id', 'user_id', 'score', 'recommendation', 'comments'])]
class AbstractReview extends Model
{
    /** @use HasFactory<AbstractReviewFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<AbstractSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(AbstractSubmission::class, 'abstract_submission_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'recommendation' => ReviewRecommendation::class,
        ];
    }
}
