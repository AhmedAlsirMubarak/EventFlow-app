<?php

namespace App\Models;

use Database\Factories\AbstractTrackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['abstract_call_id', 'name', 'description'])]
class AbstractTrack extends Model
{
    /** @use HasFactory<AbstractTrackFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<AbstractCall, $this>
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(AbstractCall::class, 'abstract_call_id');
    }
}
