<?php

namespace App\Models;

use Database\Factories\BoothFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['floor_plan_id', 'organization_id', 'booth_number', 'x', 'y', 'width', 'height', 'notes'])]
class Booth extends Model
{
    /** @use HasFactory<BoothFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<FloorPlan, $this>
     */
    public function floorPlan(): BelongsTo
    {
        return $this->belongsTo(FloorPlan::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'width' => 'float',
            'height' => 'float',
        ];
    }
}
