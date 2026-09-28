<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Deleting one of these records moves it to "Recently deleted" where it can be
 * restored (undo), rather than destroying it outright. The scheduled
 * `model:prune` command (routes/console.php) removes it permanently once it has
 * been in the bin for RETENTION_DAYS.
 */
trait Restorable
{
    use Prunable;
    use SoftDeletes;

    public const RETENTION_DAYS = 30;

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(self::RETENTION_DAYS));
    }
}
